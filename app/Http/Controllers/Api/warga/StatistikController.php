<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatistikController extends Controller
{
    /**
     * GET /api/statistik
     *
     * Mengembalikan angka-angka untuk dashboard Flutter.
     * Otomatis filter by user kalau role = warga
     * (warga hanya lihat statistik laporannya sendiri).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query: kalau warga → filter by user_id
        //             kalau admin/petugas → semua laporan
        $query = Laporan::whereNull('deleted_at');

        if ($user->role === 'warga') {
            $query->where('user_id', $user->id);
        }

        // Hitung per status
        $totalLaporan    = (clone $query)->count();
        $totalPending    = (clone $query)->where('status', 'pending')->count();
        $totalVerifikasi = (clone $query)->where('status', 'verifikasi')->count();
        $totalDiproses   = (clone $query)->where('status', 'diproses')->count();
        $totalSelesai    = (clone $query)->where('status', 'selesai')->count();
        $totalDitolak    = (clone $query)->where('status', 'ditolak')->count();

        // Laporan per kategori (untuk pie chart di dashboard)
        $perKategori = Kategori::withCount([
            'laporan as jumlah' => function ($q) use ($user) {
                $q->whereNull('deleted_at');
                if ($user->role === 'warga') {
                    $q->where('user_id', $user->id);
                }
            }
        ])
        ->having('jumlah', '>', 0)
        ->get(['id', 'nama', 'warna', 'icon'])
        ->map(fn($k) => [
            'kategori_id' => $k->id,
            'nama'        => $k->nama,
            'warna'       => $k->warna,
            'icon'        => $k->icon,
            'jumlah'      => $k->jumlah,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statistik berhasil diambil',
            'data'    => [
                // Ini yang menggantikan angka hardcoded di dashboard Yazid
                'total'      => $totalLaporan,
                'pending'    => $totalPending,
                'verifikasi' => $totalVerifikasi,
                'diproses'   => $totalDiproses,
                'selesai'    => $totalSelesai,
                'ditolak'    => $totalDitolak,

                // Untuk chart kategori
                'per_kategori' => $perKategori,
            ],
        ], 200);
    }
}
