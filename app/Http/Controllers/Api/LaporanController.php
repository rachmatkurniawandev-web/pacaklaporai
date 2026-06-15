<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusChangeLaporanRequest;
use App\Http\Requests\StoreLaporanRequest;
use App\Http\Requests\UpdateLaporanRequest;
use App\Http\Requests\UploadFotoLaporanRequest;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\StatusHistory;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    // GET /api/laporan
    public function index(Request $request)
    {
        $query = Laporan::with(['user', 'kategori', 'dinas', 'fotos'])
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Warga hanya lihat laporan miliknya sendiri
        if (Auth::user()->role === 'warga') {
            $query->where('user_id', Auth::id());
        }

        $laporan = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Data laporan berhasil diambil',
            'data' => $laporan,
        ], 200);
    }

    // POST /api/laporan
    // POST /api/laporan
    public function store(StoreLaporanRequest $request)
    {
        $laporan = Laporan::create([
            'nomor_tiket' => Laporan::generateNomorTiket(),
            'user_id' => Auth::id(),
            'kategori_id' => $request->kategori_id,
            'dinas_id' => $request->dinas_id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'tanggal_kejadian' => $request->tanggal_kejadian,
            'prioritas' => $request->prioritas ?? 'sedang',
            'status' => 'pending',
        ]);

        // Catat history: transisi dari NULL (belum ada status) -> pending
        StatusHistory::create([
            'laporan_id' => $laporan->id,
            'user_id' => Auth::id(),
            'status_dari' => null,
            'status_ke' => 'pending',
            'keterangan' => 'Laporan baru dibuat oleh warga',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibuat',
            'data' => $laporan->load(['kategori', 'dinas']),
        ], 201);
    }

    // GET /api/laporan/{id}
    public function show($id)
    {
        $laporan = Laporan::with(['user', 'kategori', 'dinas', 'fotos', 'statusHistories', 'rating'])
            ->find($id);

        if (! $laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        if (Auth::user()->role === 'warga' && $laporan->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail laporan berhasil diambil',
            'data' => $laporan,
        ], 200);
    }

    // PUT /api/laporan/{id}
    public function update(UpdateLaporanRequest $request, $id)
    {
        $laporan = Laporan::find($id);

        if (! $laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        if (Auth::user()->role === 'warga') {
            if ($laporan->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah laporan ini',
                ], 403);
            }

            if ($laporan->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak bisa diubah karena sudah diproses',
                ], 422);
            }
        }

        $laporan->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diupdate',
            'data' => $laporan->load(['kategori', 'dinas']),
        ], 200);
    }

    // DELETE /api/laporan/{id}
    public function destroy($id)
    {
        $laporan = Laporan::find($id);

        if (! $laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        if (Auth::user()->role === 'warga' && $laporan->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus laporan ini',
            ], 403);
        }

        $laporan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dihapus',
        ], 200);
    }

    // POST /api/laporan/{id}/upload-foto
    public function uploadFoto(UploadFotoLaporanRequest $request, $id)
    {
        $laporan = Laporan::find($id);

        if (! $laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        // Otorisasi: warga hanya bisa upload foto ke laporannya sendiri
        if (Auth::user()->role === 'warga' && $laporan->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini',
            ], 403);
        }

        // ====================================================
        // ====================================================
        // UPLOAD KE CLOUDINARY
        // ====================================================
        $uploadedFile = $request->file('foto');

        $result = Cloudinary::uploadApi()->upload($uploadedFile->getRealPath(), [
            'folder' => 'pacaklaporai/laporan',
        ]);

        $urlFoto = $result['secure_url'];
        $publicId = $result['public_id'];

        // Hitung urutan foto (foto terakhir + 1)
        $urutanTerakhir = LaporanFoto::where('laporan_id', $laporan->id)->max('urutan') ?? 0;

        // Jika is_primary = true, set semua foto lain jadi false dulu
        $isPrimary = $request->boolean('is_primary');
        if ($isPrimary) {
            LaporanFoto::where('laporan_id', $laporan->id)->update(['is_primary' => false]);
        }

        $foto = LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'url' => $urlFoto,
            'public_id' => $publicId,
            'urutan' => $urutanTerakhir + 1,
            'is_primary' => $isPrimary,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil diupload',
            'data' => $foto,
        ], 201);
    }

    // PUT /api/laporan/{id}/status-change
    public function statusChange(StatusChangeLaporanRequest $request, $id)
    {
        $laporan = Laporan::find($id);

        if (! $laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        // Otorisasi: hanya petugas dan admin
        if (! in_array(Auth::user()->role, ['petugas', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah status laporan',
            ], 403);
        }

        $statusBaru = $request->status;
        $statusLama = $laporan->status;

        // Validasi transisi status
        if (! $laporan->bisaTransisiKe($statusBaru)) {
            return response()->json([
                'success' => false,
                'message' => "Status tidak bisa diubah dari '{$statusLama}' ke '{$statusBaru}'",
            ], 422);
        }

        // Update data laporan
        $dataUpdate = ['status' => $statusBaru];

        if ($request->filled('catatan_petugas')) {
            $dataUpdate['catatan_petugas'] = $request->catatan_petugas;
        }

        if ($statusBaru === 'ditolak') {
            $dataUpdate['alasan_ditolak'] = $request->alasan_ditolak;
        }

        if ($statusBaru === 'selesai') {
            $dataUpdate['tanggal_selesai'] = now();
        }

        $laporan->update($dataUpdate);

        // Catat history transisi
        StatusHistory::create([
            'laporan_id' => $laporan->id,
            'user_id' => Auth::id(),
            'status_dari' => $statusLama,
            'status_ke' => $statusBaru,
            'keterangan' => $request->keterangan ?? "Status diubah dari {$statusLama} menjadi {$statusBaru}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diubah',
            'data' => $laporan->load(['kategori', 'dinas', 'statusHistories']),
        ], 200);
    }
}
