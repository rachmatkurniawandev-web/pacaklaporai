<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    // GET /api/notifikasi
    public function index(Request $request)
    {
        $notifikasi = Notifikasi::with('laporan:id,nomor_tiket,judul,status')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        // Hitung jumlah yang belum dibaca (untuk badge UI di app)
        $unreadCount = Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'message'      => 'Data notifikasi berhasil diambil',
            'unread_count' => $unreadCount,
            'data'         => $notifikasi,
        ], 200);
    }

    // PUT /api/notifikasi/{id}/read
    public function markAsRead($id)
    {
        $notifikasi = Notifikasi::find($id);

        if (! $notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan',
            ], 404);
        }

        // Otorisasi: hanya pemilik notifikasi
        if ($notifikasi->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke notifikasi ini',
            ], 403);
        }

        // Idempotent: kalau sudah dibaca, tidak perlu update lagi
        if (! $notifikasi->is_read) {
            $notifikasi->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi telah ditandai sudah dibaca',
            'data'    => $notifikasi->fresh(),
        ], 200);
    }

    // PUT /api/notifikasi/read-all
    public function markAllAsRead()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah ditandai sudah dibaca',
        ], 200);
    }
}