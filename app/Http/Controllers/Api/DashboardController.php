<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                'summary'         => $this->getSummary(),
                'trend_mingguan'  => $this->getTrendMingguan(),
                'per_kategori'    => $this->getPerKategori(),
                'per_dinas'       => $this->getPerDinas(),
                'rating_average'  => $this->getRatingAverage(),
                'recent_laporan'  => $this->getRecentLaporan(),
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
            'pending'       => $perStatus['pending']    ?? 0,
            'verifikasi'    => $perStatus['verifikasi'] ?? 0,
            'diproses'      => $perStatus['diproses']   ?? 0,
            'selesai'       => $perStatus['selesai']    ?? 0,
            'ditolak'       => $perStatus['ditolak']    ?? 0,
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
                'hari'    => Carbon::parse($date)->format('D'), // Mon, Tue, dll
                'jumlah'  => $raw[$date] ?? 0,
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
}