<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaporanFoto;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageEnhancementController extends Controller
{
    /**
     * POST /api/admin/foto/{id}/enhance
     *
     * Admin kirim nilai brightness/contrast/sharpness,
     * backend proses gambar, simpan hasil ke Cloudinary.
     *
     * Foto asli TIDAK dihapus — disimpan di kolom 'url'.
     * Foto enhanced disimpan di kolom 'enhanced_url'.
     */
    public function enhance(Request $request, $id)
    {
        // Hanya admin dan petugas yang boleh
        if (! in_array(Auth::user()->role, ['admin', 'petugas'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk enhancement foto',
            ], 403);
        }

        // Validasi input
        $request->validate([
            'brightness' => 'required|integer|min:-100|max:100',
            // Kecerahan gambar. Negatif = gelap, positif = terang
            // Contoh: -50 = lebih gelap, 50 = lebih terang

            'contrast' => 'required|integer|min:-100|max:100',
            // Kontras gambar. Positif = kontras lebih tajam

            'sharpness' => 'required|integer|min:0|max:100',
            // Ketajaman gambar. 0 = tidak ada sharpening, 100 = maksimal
        ]);

        // Cari foto
        $foto = LaporanFoto::find($id);
        if (! $foto) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan',
            ], 404);
        }

        // ============================================================
        // PROSES GAMBAR DENGAN INTERVENTION IMAGE
        // ============================================================
        // Cara kerjanya:
        // 1. Download foto dari URL Cloudinary ke memory
        // 2. Apply perubahan brightness/contrast/sharpness
        // 3. Simpan sementara ke storage lokal
        // 4. Upload ke Cloudinary sebagai foto baru
        // 5. Simpan URL hasil ke database
        // ============================================================

        try {
            // Step 1: Buat image dari URL foto asli
            $manager = new ImageManager(new Driver);
            $image = $manager->read(file_get_contents($foto->url));

            // Step 2: Apply brightness
            if ($request->brightness !== 0) {
                $image->brightness($request->brightness);
            }

            // Step 3: Apply contrast
            if ($request->contrast !== 0) {
                $image->contrast($request->contrast);
            }

            // Step 4: Apply sharpness
            if ($request->sharpness > 0) {
                $image->sharpen($request->sharpness);
            }

            // Step 5: Simpan sementara ke local storage
            $tempPath = storage_path('app/temp_enhanced_'.$id.'_'.time().'.jpg');
            $image->toJpeg(90)->save($tempPath);

            // Step 6: Upload foto enhanced ke Cloudinary
            // Simpan di folder terpisah supaya mudah dibedakan
            $result = Cloudinary::uploadApi()->upload($tempPath, [
                'folder' => 'pacaklaporai/enhanced',
            ]);

            // Step 7: Hapus file temp
            unlink($tempPath);

            // Step 8: Kalau ada foto enhanced sebelumnya di Cloudinary, hapus
            // Supaya tidak menumpuk file yang tidak terpakai
            if ($foto->enhanced_public_id) {
                try {
                    Cloudinary::uploadApi()->destroy($foto->enhanced_public_id);
                } catch (\Exception $e) {
                    // Tidak masalah kalau gagal delete yang lama
                    // Tetap lanjut simpan yang baru
                }
            }

            // Step 9: Update database
            $foto->update([
                'enhanced_url' => $result['secure_url'],
                'enhanced_public_id' => $result['public_id'],
                'brightness' => $request->brightness,
                'contrast' => $request->contrast,
                'sharpness' => $request->sharpness,
                'is_enhanced' => true,
                'enhanced_by' => Auth::id(),
                'enhanced_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil di-enhance',
                'data' => [
                    'foto_asli' => $foto->url,
                    'foto_enhanced' => $foto->enhanced_url,
                    'parameter' => [
                        'brightness' => $request->brightness,
                        'contrast' => $request->contrast,
                        'sharpness' => $request->sharpness,
                    ],
                    'enhanced_by' => Auth::user()->name,
                    'enhanced_at' => $foto->enhanced_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            // Kalau ada file temp yang tertinggal, hapus
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses gambar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/foto/{id}/enhance-history
     *
     * Riwayat enhancement foto — siapa yang enhance,
     * kapan, dan parameter apa yang dipakai.
     */
    public function history($id)
    {
        if (! in_array(Auth::user()->role, ['admin', 'petugas'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses',
            ], 403);
        }

        $foto = LaporanFoto::with([
            'laporan:id,nomor_tiket,judul',
            'enhancedBy:id,name,email', // relasi ke user yang enhance
        ])->find($id);

        if (! $foto) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat enhancement berhasil diambil',
            'data' => [
                'foto_id' => $foto->id,
                'laporan' => $foto->laporan,
                'foto_asli' => $foto->url,
                'foto_enhanced' => $foto->enhanced_url,
                'is_enhanced' => $foto->is_enhanced,
                'parameter' => [
                    'brightness' => $foto->brightness,
                    'contrast' => $foto->contrast,
                    'sharpness' => $foto->sharpness,
                ],
                'enhanced_by' => $foto->enhancedBy,
                'enhanced_at' => $foto->enhanced_at,
            ],
        ], 200);
    }

    /**
     * DELETE /api/admin/foto/{id}/enhance
     *
     * Reset enhancement — hapus foto enhanced,
     * kembalikan ke foto asli.
     */
    public function reset($id)
    {
        if (! in_array(Auth::user()->role, ['admin', 'petugas'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses',
            ], 403);
        }

        $foto = LaporanFoto::find($id);
        if (! $foto) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan',
            ], 404);
        }

        if (! $foto->is_enhanced) {
            return response()->json([
                'success' => false,
                'message' => 'Foto ini belum pernah di-enhance',
            ], 422);
        }

        // Hapus foto enhanced dari Cloudinary
        if ($foto->enhanced_public_id) {
            try {
                Cloudinary::uploadApi()->destroy($foto->enhanced_public_id);
            } catch (\Exception $e) {
                // Lanjut meski gagal delete di Cloudinary
            }
        }

        // Reset semua kolom enhancement
        $foto->update([
            'enhanced_url' => null,
            'enhanced_public_id' => null,
            'brightness' => 0,
            'contrast' => 0,
            'sharpness' => 0,
            'is_enhanced' => false,
            'enhanced_by' => null,
            'enhanced_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enhancement berhasil direset, foto kembali ke asli',
            'data' => ['foto_asli' => $foto->url],
        ], 200);
    }
}
