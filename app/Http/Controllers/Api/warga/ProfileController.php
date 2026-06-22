<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // GET /api/profile
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diambil',
            'data'    => $user,
        ], 200);
    }

    // PUT /api/profile
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $dataUpdate = [];

        // Field biasa yang boleh diupdate
        foreach (['name', 'email', 'nik', 'telepon', 'alamat'] as $field) {
            if ($request->has($field)) {
                $dataUpdate[$field] = $request->input($field);
            }
        }

        // Upload foto profil ke Cloudinary
        if ($request->hasFile('foto_profil')) {
            $uploadedFile = $request->file('foto_profil');

            $result = Cloudinary::uploadApi()->upload($uploadedFile->getRealPath(), [
                'folder' => 'pacaklaporai/profil',
            ]);

            $dataUpdate['foto_profil'] = $result['secure_url'];
        }

        // Ganti password (kalau ada)
        if ($request->filled('new_password')) {
            // Verifikasi password lama
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama tidak sesuai',
                ], 422);
            }

            $dataUpdate['password'] = Hash::make($request->new_password);
        }

        if (empty($dataUpdate)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang diubah',
            ], 422);
        }

        $user->update($dataUpdate);

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate',
            'data'    => $user->fresh(), // ambil data terbaru dari database
        ], 200);
    }
}