<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AiClassificationService;

class DashboardController extends Controller
{
    // GET /api/dashboard/stats
    public function stats()
    {
        // Otorisasi: hanya admin & petugas
        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'petugas'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke dashboard',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats berhasil diambil',
            'data' => [
                'summary' => $this->getSummary(),
                'trend_mingguan' => $this->getTrendMingguan(),
                'per_kategori' => $this->getPerKategori(),
                'per_dinas' => $this->getPerDinas(),
                'rating_average' => $this->getRatingAverage(),
                'recent_laporan' => $this->getRecentLaporan(),
                'avg_response_time' => $this->getAvgResponseTime(),
                'regional_hotspots' => $this->getRegionalHotspots(),
                'ai_insight'        => $this->getAiInsight(),   // ← tambahkan ini
            ],
        ], 200);
    }

    /**
     * Total laporan + breakdown per status
     */
    private function getSummary(): array
    {
        $total = Laporan::count();

        $perStatus = Laporan::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total_laporan' => $total,
            'pending' => $perStatus['pending'] ?? 0,
            'verifikasi' => $perStatus['verifikasi'] ?? 0,
            'diproses' => $perStatus['diproses'] ?? 0,
            'selesai' => $perStatus['selesai'] ?? 0,
            'ditolak' => $perStatus['ditolak'] ?? 0,
        ];
    }

    /**
     * Trend laporan 7 hari terakhir untuk chart line
     */
    private function getTrendMingguan(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();

        $raw = Laporan::where('created_at', '>=', $sevenDaysAgo)
            ->select(DB::raw('DATE(created_at) as tanggal'), DB::raw('count(*) as jumlah'))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->pluck('jumlah', 'tanggal')
            ->toArray();

        // Isi tanggal yang kosong dengan 0 (supaya chart tidak loncat)
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'tanggal' => $date,
                'hari' => Carbon::parse($date)->format('D'), // Mon, Tue, dll
                'jumlah' => $raw[$date] ?? 0,
            ];
        }

        return $trend;
    }

    /**
     * Laporan per kategori untuk chart bar/pie
     */
    private function getPerKategori(): array
    {
        return Laporan::join('kategori', 'laporan.kategori_id', '=', 'kategori.id')
            ->select(
                'kategori.id',
                'kategori.nama',
                'kategori.warna',
                'kategori.icon',
                DB::raw('count(laporan.id) as jumlah')
            )
            ->groupBy('kategori.id', 'kategori.nama', 'kategori.warna', 'kategori.icon')
            ->orderByDesc('jumlah')
            ->get()
            ->toArray();
    }

    /**
     * Laporan per dinas
     */
    private function getPerDinas(): array
    {
        return Laporan::join('dinas', 'laporan.dinas_id', '=', 'dinas.id')
            ->select(
                'dinas.id',
                'dinas.nama',
                'dinas.kode',
                DB::raw('count(laporan.id) as jumlah')
            )
            ->groupBy('dinas.id', 'dinas.nama', 'dinas.kode')
            ->orderByDesc('jumlah')
            ->get()
            ->toArray();
    }

    /**
     * Rata-rata rating semua laporan selesai
     */
    private function getRatingAverage(): float
    {
        return round(Rating::avg('nilai_rating') ?? 0, 2);
    }

    /**
     * 5 laporan terbaru dengan eager load
     */
    private function getRecentLaporan()
    {
        return Laporan::with([
            'user:id,name,foto_profil',
            'kategori:id,nama,warna,icon',
            'dinas:id,nama,kode',
        ])
            ->latest()
            ->take(5)
            ->get();
    }

    // ================================================================
    // TAMBAHAN BARU — Untuk Trend Analysis (Mockup #6)
    // ================================================================

    /**
     * Average Response Time
     *
     * Rata-rata waktu dari laporan dibuat (created_at)
     * sampai laporan selesai (tanggal_selesai).
     *
     * Di mockup terlihat "Avg. Response Time: 14.2 min"
     * Kita hitung dalam menit.
     *
     * Kenapa penting? Ini KPI utama pemerintah —
     * seberapa cepat laporan warga ditangani.
     */
    private function getAvgResponseTime(): array
    {
        // Hanya laporan yang sudah selesai yang punya waktu respons valid
        $laporanSelesai = Laporan::whereNotNull('tanggal_selesai')
            ->where('status', 'selesai')
            ->select('created_at', 'tanggal_selesai')
            ->get();

        if ($laporanSelesai->isEmpty()) {
            return [
                'rata_rata_menit' => 0,
                'rata_rata_jam' => 0,
                'rata_rata_hari' => 0,
                'label' => '0 menit',
                'total_selesai' => 0,
            ];
        }

        // Hitung rata-rata dalam menit
        $totalMenit = $laporanSelesai->sum(function ($laporan) {
            return Carbon::parse($laporan->created_at)
                ->diffInMinutes(Carbon::parse($laporan->tanggal_selesai));
        });

        $rataRataMenit = round($totalMenit / $laporanSelesai->count(), 1);
        $rataRataJam = round($rataRataMenit / 60, 1);
        $rataRataHari = round($rataRataJam / 24, 1);

        // Buat label yang mudah dibaca
        // Contoh: "14.2 menit", "2.5 jam", "1.3 hari"
        if ($rataRataMenit < 60) {
            $label = $rataRataMenit.' menit';
        } elseif ($rataRataJam < 24) {
            $label = $rataRataJam.' jam';
        } else {
            $label = $rataRataHari.' hari';
        }

        return [
            'rata_rata_menit' => $rataRataMenit,
            'rata_rata_jam' => $rataRataJam,
            'rata_rata_hari' => $rataRataHari,
            'label' => $label,
            'total_selesai' => $laporanSelesai->count(),
        ];
    }

    /**
     * Regional Hotspots per Kecamatan
     *
     * Di mockup terlihat:
     * "Top District: Kec. Menteng — 156 Reports this week"
     * "Highest Growth: Kec. Penjaringan — +42% Growth rate"
     *
     * Karena kita tidak punya kolom 'kecamatan' di database,
     * kita ekstrak nama kecamatan dari kolom 'lokasi' (text),
     * atau grouping berdasarkan area koordinat lat/lng.
     *
     * Untuk demo, kita pakai pendekatan sederhana:
     * ambil kata kunci dari kolom 'lokasi' dan kelompokkan.
     */
    private function getRegionalHotspots(): array
    {
        // Ambil semua laporan yang punya lokasi
        $laporan = Laporan::whereNotNull('lokasi')
            ->whereNull('deleted_at')
            ->select('lokasi', 'status', 'created_at')
            ->get();

        // Daftar kecamatan di Palembang untuk pencocokan
        $kecamatanPalembang = [
            'Ilir Barat I', 'Ilir Barat II', 'Ilir Timur I', 'Ilir Timur II', 'Ilir Timur III',
            'Seberang Ulu I', 'Seberang Ulu II', 'Kemuning', 'Kalidoni', 'Bukit Kecil',
            'Gandus', 'Kertapati', 'Plaju', 'Sako', 'Sukarami', 'Alang-Alang Lebar',
            'Sematang Borang', 'Jakabaring', 'Veteran', 'Demang',
        ];

        // Kelompokkan laporan berdasarkan kecamatan yang cocok
        $regionalData = [];

        foreach ($laporan as $item) {
            $kecamatanDitemukan = 'Lainnya';

            foreach ($kecamatanPalembang as $kec) {
                if (stripos($item->lokasi, $kec) !== false) {
                    $kecamatanDitemukan = $kec;
                    break;
                }
            }

            if (! isset($regionalData[$kecamatanDitemukan])) {
                $regionalData[$kecamatanDitemukan] = [
                    'kecamatan' => $kecamatanDitemukan,
                    'total' => 0,
                    'minggu_ini' => 0,
                    'minggu_lalu' => 0,
                ];
            }

            $regionalData[$kecamatanDitemukan]['total']++;

            // Hitung laporan minggu ini vs minggu lalu untuk growth rate
            $createdAt = Carbon::parse($item->created_at);
            if ($createdAt->isAfter(Carbon::now()->subWeek())) {
                $regionalData[$kecamatanDitemukan]['minggu_ini']++;
            } elseif ($createdAt->isAfter(Carbon::now()->subWeeks(2))) {
                $regionalData[$kecamatanDitemukan]['minggu_lalu']++;
            }
        }

        // Hitung growth rate dan sort
        $result = collect($regionalData)
            ->map(function ($item) {
                // Growth rate = ((minggu_ini - minggu_lalu) / minggu_lalu) * 100
                $growth = 0;
                if ($item['minggu_lalu'] > 0) {
                    $growth = round(
                        (($item['minggu_ini'] - $item['minggu_lalu']) / $item['minggu_lalu']) * 100,
                        1
                    );
                } elseif ($item['minggu_ini'] > 0) {
                    $growth = 100; // Naik dari 0 ke ada = 100%
                }

                return array_merge($item, ['growth_rate' => $growth]);
            })
            ->sortByDesc('total')
            ->values();

        // Ambil top district dan highest growth untuk summary card
        $topDistrict = $result->first();
        $highestGrowth = $result->sortByDesc('growth_rate')->first();

        return [
            'top_district' => $topDistrict ? [
                'kecamatan' => $topDistrict['kecamatan'],
                'total' => $topDistrict['total'],
                'minggu_ini' => $topDistrict['minggu_ini'],
            ] : null,
            'highest_growth' => $highestGrowth ? [
                'kecamatan' => $highestGrowth['kecamatan'],
                'growth_rate' => $highestGrowth['growth_rate'],
            ] : null,
            'semua_kecamatan' => $result->take(10)->values(),
        ];
    }





        /**
     * AI Insight untuk banner di dashboard
     * "Wilayah Sako memiliki jumlah laporan banjir tertinggi bulan ini"
     */
    private function getAiInsight(): array
    {
        // Ambil data hotspot dan kategori untuk generate insight
        $hotspots    = $this->getRegionalHotspots();
        $perKategori = $this->getPerKategori();
 
        // Format hotspot untuk AiClassificationService
        $hotspotFormatted = [];
        if (isset($hotspots['top_district']) && $hotspots['top_district']) {
            $hotspotFormatted[] = [
                'label'           => $hotspots['top_district']['kecamatan'],
                'jumlah_laporan'  => $hotspots['top_district']['total'],
                'kategori_dominan'=> $perKategori[0]['nama'] ?? 'laporan',
            ];
        }
 
        $insightTeks = AiClassificationService::generateDashboardInsight(
            $hotspotFormatted,
            $perKategori
        );
 
        return [
            'teks'        => $insightTeks,
            'generated_at'=> now()->toISOString(),
            'model'       => 'PacakLaporAI-v1.0',
        ];
    }
}
