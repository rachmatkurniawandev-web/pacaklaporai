<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgencyManagementController extends Controller
{
    /**
     * Cek apakah user adalah admin
     */
    private function adminOnly(): bool
    {
        return Auth::user()->role === 'admin';
    }

    /**
     * GET /api/admin/dinas
     *
     * List semua dinas dengan summary card.
     * Di mockup terlihat: Total Agencies, Active Tasks,
     * Assigned Staff, Overdue Tasks.
     */
    public function index(Request $request)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa mengakses manajemen dinas',
            ], 403);
        }

        $query = Dinas::withCount([
            // Hitung total laporan yang ditugaskan ke dinas ini
            'laporan as total_laporan',

            // Hitung laporan aktif (belum selesai/ditolak)
            'laporan as laporan_aktif' => fn($q) => $q->whereIn('status', ['verifikasi', 'diproses']),

            // Hitung laporan selesai
            'laporan as laporan_selesai' => fn($q) => $q->where('status', 'selesai'),
        ]);

        // Filter by status aktif
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by nama atau kode
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('kode', 'like', '%' . $request->search . '%');
            });
        }

        $dinas = $query->orderBy('nama')->paginate($request->input('per_page', 15));

        // Summary cards untuk mockup Agency Management
        $summary = [
            'total_agencies'  => Dinas::count(),
            'active_agencies' => Dinas::where('is_active', true)->count(),
            'active_tasks'    => Laporan::whereIn('status', ['verifikasi', 'diproses'])
                                    ->whereNotNull('dinas_id')->count(),
            'total_laporan'   => Laporan::whereNotNull('dinas_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data dinas berhasil diambil',
            'summary' => $summary,
            'data'    => $dinas,
        ], 200);
    }

    /**
     * POST /api/admin/dinas
     *
     * Tambah dinas baru.
     */
    public function store(Request $request)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa menambah dinas',
            ], 403);
        }

        $request->validate([
            'nama'      => 'required|string|max:255',
            'kode'      => 'required|string|max:10|unique:dinas,kode',
            'email'     => 'nullable|email',
            'telepon'   => 'nullable|string|max:20',
            'alamat'    => 'nullable|string',
            'deskripsi' => 'nullable|string',
        ]);

        $dinas = Dinas::create([
            'nama'      => $request->nama,
            'kode'      => strtoupper($request->kode), // kode selalu uppercase
            'email'     => $request->email,
            'telepon'   => $request->telepon,
            'alamat'    => $request->alamat,
            'deskripsi' => $request->deskripsi,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dinas berhasil ditambahkan',
            'data'    => $dinas,
        ], 201);
    }

    /**
     * GET /api/admin/dinas/{id}
     *
     * Detail satu dinas + statistik laporan yang ditangani.
     */
    public function show($id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa melihat detail dinas',
            ], 403);
        }

        $dinas = Dinas::withCount([
            'laporan as total_laporan',
            'laporan as laporan_pending'    => fn($q) => $q->where('status', 'pending'),
            'laporan as laporan_diproses'   => fn($q) => $q->where('status', 'diproses'),
            'laporan as laporan_selesai'    => fn($q) => $q->where('status', 'selesai'),
            'laporan as laporan_ditolak'    => fn($q) => $q->where('status', 'ditolak'),
        ])->find($id);

        if (! $dinas) {
            return response()->json([
                'success' => false,
                'message' => 'Dinas tidak ditemukan',
            ], 404);
        }

        // 5 laporan terbaru yang ditangani dinas ini
        $recentLaporan = Laporan::with('user:id,name')
            ->where('dinas_id', $id)
            ->latest()
            ->take(5)
            ->get(['id', 'nomor_tiket', 'judul', 'status', 'user_id', 'created_at']);

        return response()->json([
            'success' => true,
            'message' => 'Detail dinas berhasil diambil',
            'data'    => array_merge($dinas->toArray(), [
                'recent_laporan' => $recentLaporan,
            ]),
        ], 200);
    }

    /**
     * PUT /api/admin/dinas/{id}
     *
     * Update data dinas.
     */
    public function update(Request $request, $id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa mengubah data dinas',
            ], 403);
        }

        $dinas = Dinas::find($id);
        if (! $dinas) {
            return response()->json([
                'success' => false,
                'message' => 'Dinas tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama'      => 'sometimes|string|max:255',
            'kode'      => 'sometimes|string|max:10|unique:dinas,kode,' . $id,
            // unique tapi exclude ID ini sendiri supaya tidak konflik saat update
            'email'     => 'nullable|email',
            'telepon'   => 'nullable|string|max:20',
            'alamat'    => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->only(['nama', 'email', 'telepon', 'alamat', 'deskripsi', 'is_active']);

        // Kalau kode diupdate, uppercase-kan
        if ($request->filled('kode')) {
            $data['kode'] = strtoupper($request->kode);
        }

        $dinas->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data dinas berhasil diupdate',
            'data'    => $dinas->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/admin/dinas/{id}
     *
     * Nonaktifkan dinas (bukan hapus permanen).
     * Data laporan yang pernah ditangani dinas tetap tersimpan.
     */
    public function destroy($id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa menonaktifkan dinas',
            ], 403);
        }

        $dinas = Dinas::find($id);
        if (! $dinas) {
            return response()->json([
                'success' => false,
                'message' => 'Dinas tidak ditemukan',
            ], 404);
        }

        // Cek apakah masih ada laporan aktif di dinas ini
        $laporanAktif = Laporan::where('dinas_id', $id)
            ->whereIn('status', ['verifikasi', 'diproses'])
            ->count();

        if ($laporanAktif > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak bisa menonaktifkan dinas yang masih punya {$laporanAktif} laporan aktif. Selesaikan atau pindahkan laporan terlebih dahulu.",
            ], 422);
        }

        $dinas->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => "Dinas {$dinas->nama} berhasil dinonaktifkan",
        ], 200);
    }
}