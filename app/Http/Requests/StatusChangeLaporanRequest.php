<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusChangeLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi role dicek di controller
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:verifikasi,diproses,selesai,ditolak',
            'keterangan' => 'nullable|string|max:1000',
            'catatan_petugas' => 'nullable|string',
            'alasan_ditolak' => 'required_if:status,ditolak|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status baru wajib diisi',
            'status.in' => 'Status tidak valid',
            'alasan_ditolak.required_if' => 'Alasan penolakan wajib diisi jika status ditolak',
        ];
    }
}