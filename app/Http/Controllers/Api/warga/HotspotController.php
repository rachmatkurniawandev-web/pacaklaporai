<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;

class HotspotController extends Controller
{
    /**
     * GET /api/hotspot
     *
     * Mengembalikan titik-titik hotspot berdasarkan
     * kumpulan laporan yang punya koordinat lat/lng.
     *
     * Query params (semua opsional):
     *   ?kategori_id=2     → filter kategori tertentu
     *   ?status=pending    → filter status tertentu
     *   ?radius=500        → radius pengelompokan dalam meter (default 500)
     *   ?limit=30          → max jumlah titik hotspot (default 30)
     */
    public function index(Request $request)
    {
        // ============================================================
        // STEP 1: Ambil semua laporan yang punya koordinat
        // ============================================================
        // Kenapa harus ada lat/lng? Karena tanpa koordinat,
        // laporan tidak bisa ditampilkan di peta.
        $query = Laporan::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNull('deleted_at'); // exclude laporan yang sudah dihapus

        // Filter opsional: kategori
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Filter opsional: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ambil data yang dibutuhkan saja (hemat memori)
        $semuaLaporan = $query->with('kategori:id,nama,warna')
            ->select('id', 'latitude', 'longitude', 'status', 'kategori_id', 'lokasi', 'created_at')
            ->get();

        // ============================================================
        // STEP 2: Kelompokkan laporan yang berdekatan
        // ============================================================
        // Konsepnya: kalau ada 5 laporan dalam radius 500m,
        // tampilkan sebagai 1 titik hotspot (bukan 5 titik terpisah).
        //
        // Analoginya: Google Maps tidak tampilkan 100 pin individual
        // saat kamu zoom out, dia kelompokkan jadi 1 cluster "100".
        // Inilah yang disebut "clustering".

        $radiusMeter = $request->input('radius', 500); // default 500 meter
        $limitHotspot = $request->input('limit', 30);  // default 30 titik

        $clusters = $this->clusterLaporan($semuaLaporan, $radiusMeter);

        // ============================================================
        // STEP 3: Format response untuk Flutter
        // ============================================================
        $hotspots = collect($clusters)
            ->sortByDesc('jumlah_laporan') // urut dari yang paling banyak laporan
            ->take($limitHotspot)
            ->map(function ($cluster) {
                return [
                    'latitude'          => round($cluster['latitude'], 6),
                    'longitude'         => round($cluster['longitude'], 6),
                    'jumlah_laporan'    => $cluster['jumlah_laporan'],
                    'label'             => $cluster['label'],
                    'kategori_dominan'  => $cluster['kategori_dominan'],
                    'warna_kategori'    => $cluster['warna_kategori'],
                    'status_breakdown'  => $cluster['status_breakdown'],
                    'intensitas'        => $this->hitungIntensitas($cluster['jumlah_laporan']),
                ];
            })
            ->values(); // reset index supaya jadi array biasa [0,1,2,...]

        return response()->json([
            'success'        => true,
            'message'        => 'Data hotspot berhasil diambil',
            'total_laporan'  => $semuaLaporan->count(), // info berapa total laporan yang diproses
            'total_hotspot'  => $hotspots->count(),
            'data'           => $hotspots,
        ], 200);
    }

    // ================================================================
    // HELPER: Clustering sederhana tanpa library eksternal
    // ================================================================
    // Cara kerjanya:
    // 1. Ambil laporan pertama → jadikan cluster baru
    // 2. Cek laporan berikutnya → kalau jaraknya < radius dari cluster
    //    yang sudah ada, masukkan ke cluster itu
    // 3. Kalau tidak ada cluster yang cocok → buat cluster baru
    // 4. Ulangi sampai semua laporan masuk ke cluster
    //
    // Ini algoritma "greedy clustering" — tidak sempurna secara matematis
    // tapi cukup untuk kebutuhan demo dan performa cepat.

    private function clusterLaporan($laporan, $radiusMeter): array
    {
        $clusters = [];

        foreach ($laporan as $item) {
            $lat = (float) $item->latitude;
            $lng = (float) $item->longitude;

            $masukCluster = false;

            // Cek apakah laporan ini dekat dengan cluster yang sudah ada
            foreach ($clusters as &$cluster) {
                $jarak = $this->hitungJarak(
                    $cluster['latitude'], $cluster['longitude'],
                    $lat, $lng
                );

                if ($jarak <= $radiusMeter) {
                    // Masuk ke cluster ini
                    $cluster['laporan'][] = $item;
                    $cluster['jumlah_laporan']++;

                    // Update posisi cluster = rata-rata semua titik di dalamnya
                    // (supaya titik cluster bergeser ke "tengah" laporan)
                    $cluster['latitude'] = ($cluster['latitude'] * ($cluster['jumlah_laporan'] - 1) + $lat) / $cluster['jumlah_laporan'];
                    $cluster['longitude'] = ($cluster['longitude'] * ($cluster['jumlah_laporan'] - 1) + $lng) / $cluster['jumlah_laporan'];

                    $masukCluster = true;
                    break;
                }
            }

            // Kalau tidak cocok dengan cluster manapun → buat cluster baru
            if (!$masukCluster) {
                $clusters[] = [
                    'latitude'       => $lat,
                    'longitude'      => $lng,
                    'jumlah_laporan' => 1,
                    'laporan'        => [$item],
                ];
            }
        }

        // Setelah semua laporan masuk cluster, hitung info tambahan tiap cluster
        foreach ($clusters as &$cluster) {
            $clusterLaporan = collect($cluster['laporan']);

            // Label = ambil lokasi dari laporan terbanyak/pertama di cluster
            $cluster['label'] = $clusterLaporan->first()->lokasi ?? 'Tidak diketahui';
            // Potong supaya tidak terlalu panjang
            if (strlen($cluster['label']) > 50) {
                $cluster['label'] = substr($cluster['label'], 0, 50) . '...';
            }

            // Kategori dominan = kategori yang paling sering muncul di cluster ini
            $kategoriTerbanyak = $clusterLaporan
                ->whereNotNull('kategori')
                ->groupBy('kategori_id')
                ->sortByDesc(fn($group) => $group->count())
                ->first();

            $cluster['kategori_dominan'] = $kategoriTerbanyak
                ? $kategoriTerbanyak->first()->kategori?->nama ?? 'Lainnya'
                : 'Lainnya';

            $cluster['warna_kategori'] = $kategoriTerbanyak
                ? $kategoriTerbanyak->first()->kategori?->warna ?? '#6B7280'
                : '#6B7280';

            // Status breakdown = berapa laporan per status di cluster ini
            $cluster['status_breakdown'] = [
                'pending'    => $clusterLaporan->where('status', 'pending')->count(),
                'verifikasi' => $clusterLaporan->where('status', 'verifikasi')->count(),
                'diproses'   => $clusterLaporan->where('status', 'diproses')->count(),
                'selesai'    => $clusterLaporan->where('status', 'selesai')->count(),
                'ditolak'    => $clusterLaporan->where('status', 'ditolak')->count(),
            ];

            // Hapus array laporan mentah dari response (tidak perlu dikirim ke Flutter)
            unset($cluster['laporan']);
        }

        return $clusters;
    }

    // ================================================================
    // HELPER: Hitung jarak dua titik koordinat (dalam meter)
    // ================================================================
    // Pakai rumus Haversine — rumus standar untuk hitung jarak
    // antara dua titik di permukaan bumi.
    //
    // Kenapa tidak pakai Euclidean (rumus jarak biasa)?
    // Karena bumi itu bulat, bukan flat. Di skala kota seperti Palembang
    // bedanya kecil, tapi pakai Haversine tetap lebih tepat.

    private function hitungJarak(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $radiusBumi = 6371000; // dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radiusBumi * $c; // hasil dalam meter
    }

    // ================================================================
    // HELPER: Tentukan intensitas berdasarkan jumlah laporan
    // ================================================================
    // Flutter pakai ini untuk tentukan warna & ukuran marker di peta:
    //   tinggi  → merah, marker besar
    //   sedang  → oranye, marker sedang
    //   rendah  → hijau, marker kecil

    private function hitungIntensitas(int $jumlah): string
    {
        if ($jumlah >= 10) return 'tinggi';
        if ($jumlah >= 5)  return 'sedang';
        return 'rendah';
    }
}
