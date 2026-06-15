<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kategori_id'      => 'sometimes|nullable|exists:kategori,id',
            'dinas_id'         => 'sometimes|nullable|exists:dinas,id',
            'judul'            => 'sometimes|string|max:255',
            'deskripsi'        => 'sometimes|string',
            'lokasi'           => 'sometimes|string',
            'latitude'         => 'sometimes|nullable|numeric|between:-90,90',
            'longitude'        => 'sometimes|nullable|numeric|between:-180,180',
            'tanggal_kejadian' => 'sometimes|nullable|date',
        ];
    }
}