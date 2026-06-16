<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nilai_rating'         => 'required|integer|between:1,5',
            'komentar'             => 'nullable|string|max:1000',
            'kecepatan_respon'     => 'nullable|integer|between:1,5',
            'kualitas_penanganan'  => 'nullable|integer|between:1,5',
            'sikap_petugas'        => 'nullable|integer|between:1,5',
            'is_anonymous'         => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nilai_rating.required' => 'Nilai rating wajib diisi',
            'nilai_rating.between'  => 'Nilai rating harus antara 1-5',
        ];
    }
}