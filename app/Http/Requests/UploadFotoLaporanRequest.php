<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFotoLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto'        => 'required|image|mimes:jpeg,jpg,png|max:5120', // max 5MB
            'keterangan'  => 'nullable|string|max:255',
            'is_primary'  => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Foto wajib diupload',
            'foto.image'    => 'File harus berupa gambar',
            'foto.mimes'    => 'Format foto harus jpeg, jpg, atau png',
            'foto.max'      => 'Ukuran foto maksimal 5MB',
        ];
    }
}