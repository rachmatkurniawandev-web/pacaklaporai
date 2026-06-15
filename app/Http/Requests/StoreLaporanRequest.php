<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sudah dicek via middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            // kategori & dinas BOLEH kosong (nanti diisi AI/admin)
            'kategori_id'      => 'nullable|exists:kategori,id',
            'dinas_id'         => 'nullable|exists:dinas,id',

            'judul'            => 'required|string|max:255',
            'deskripsi'        => 'required|string',
            'lokasi'           => 'required|string',

            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',

            'tanggal_kejadian' => 'nullable|date',

            // prioritas opsional, kalau tidak dikirim pakai default 'sedang'
            'prioritas'        => 'nullable|in:rendah,sedang,tinggi,darurat',
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'     => 'Judul laporan wajib diisi',
            'deskripsi.required' => 'Deskripsi laporan wajib diisi',
            'lokasi.required'    => 'Lokasi wajib diisi',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid',
            'dinas_id.exists'    => 'Dinas yang dipilih tidak valid',
        ];
    }
}