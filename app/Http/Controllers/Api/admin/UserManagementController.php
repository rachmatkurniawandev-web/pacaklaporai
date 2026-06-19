<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Middleware helper — pastikan hanya admin yang bisa akses
     * semua method di controller ini.
     */
    private function adminOnly(): bool
    {
        return Auth::user()->role === 'admin';
    }

    /**
     * GET /api/admin/users
     *
     * List semua user dengan filter role dan status.
     * Dilengkapi pagination dan summary card.
     */
    public function index(Request $request)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa mengakses manajemen user',
            ], 403);
        }

        $query = User::query();

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status aktif/nonaktif
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by nama atau email
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()
            ->paginate($request->input('per_page', 15));

        // Summary cards untuk dashboard User Management
        $summary = [
            'total'           => User::count(),
            'active'          => User::where('is_active', true)->count(),
            'warga'           => User::where('role', 'warga')->count(),
            'petugas'         => User::where('role', 'petugas')->count(),
            'admin'           => User::where('role', 'admin')->count(),
            'pending_approve' => User::where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'summary' => $summary,
            'data'    => $users,
        ], 200);
    }

    /**
     * POST /api/admin/users
     *
     * Admin tambah user baru langsung dari dashboard.
     * Berbeda dengan register publik — ini bisa set role apapun.
     */
    public function store(Request $request)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa menambah user',
            ], 403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:warga,petugas,admin',
            'nik'      => 'required|string|size:16|unique:users,nik',
            'telepon'  => 'required|string|max:20',
            'alamat'   => 'required|string|max:255',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'nik'       => $request->nik,
            'telepon'   => $request->telepon,
            'alamat'    => $request->alamat,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'data'    => $user,
        ], 201);
    }

    /**
     * GET /api/admin/users/{id}
     *
     * Detail satu user — termasuk statistik laporan miliknya.
     */
    public function show($id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa melihat detail user',
            ], 403);
        }

        $user = User::withCount([
            'laporan as total_laporan',
            'laporan as laporan_selesai' => fn($q) => $q->where('status', 'selesai'),
            'laporan as laporan_pending' => fn($q) => $q->where('status', 'pending'),
        ])->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail user berhasil diambil',
            'data'    => $user,
        ], 200);
    }

    /**
     * PUT /api/admin/users/{id}
     *
     * Update role atau status aktif user.
     * Admin tidak bisa ubah password user lain dari sini —
     * itu dilakukan lewat reset password terpisah.
     */
    public function update(Request $request, $id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa mengubah data user',
            ], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // Jangan izinkan admin mengubah akunnya sendiri via endpoint ini
        // supaya tidak sengaja me-nonaktifkan diri sendiri
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa mengubah akun sendiri dari sini. Gunakan halaman Profile.',
            ], 422);
        }

        $request->validate([
            'role'      => 'sometimes|in:warga,petugas,admin',
            'is_active' => 'sometimes|boolean',
            'name'      => 'sometimes|string|max:255',
            'telepon'   => 'sometimes|string|max:20',
            'alamat'    => 'sometimes|string|max:255',
        ]);

        $user->update($request->only(['role', 'is_active', 'name', 'telepon', 'alamat']));

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diupdate',
            'data'    => $user->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/admin/users/{id}
     *
     * Nonaktifkan user (bukan hapus permanen).
     * Data laporan user tetap tersimpan untuk audit.
     * Analoginya: suspend akun, bukan delete akun.
     */
    public function destroy($id)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa menonaktifkan user',
            ], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menonaktifkan akun sendiri',
            ], 422);
        }

        // Nonaktifkan, bukan hapus
        $user->update(['is_active' => false]);

        // Revoke semua token aktif user ini
        // Supaya kalau dia sedang login, langsung ter-logout
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} berhasil dinonaktifkan",
        ], 200);
    }

    /**
     * GET /api/admin/users/export
     *
     * Export semua user ke format CSV.
     * Cocok untuk laporan atau audit.
     */
    public function export(Request $request)
    {
        if (! $this->adminOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa export data user',
            ], 403);
        }

        $users = User::withCount('laporan')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'nik', 'telepon', 'alamat', 'is_active', 'created_at']);

        // Buat CSV secara manual — tidak butuh library eksternal
        $csvHeader = ['ID', 'Nama', 'Email', 'Role', 'NIK', 'Telepon', 'Alamat', 'Status', 'Total Laporan', 'Bergabung'];

        $csvRows = $users->map(fn($u) => [
            $u->id,
            $u->name,
            $u->email,
            $u->role,
            $u->nik,
            $u->telepon,
            $u->alamat,
            $u->is_active ? 'Aktif' : 'Nonaktif',
            $u->laporan_count,
            $u->created_at->format('d/m/Y'),
        ]);

        // Gabungkan header + rows jadi string CSV
        $csvContent = implode(',', $csvHeader) . "\n";
        foreach ($csvRows as $row) {
            // Wrap tiap nilai dengan quotes untuk handle koma dalam teks
            $csvContent .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        // Kirim sebagai file download
        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}