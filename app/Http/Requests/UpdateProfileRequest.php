<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'             => 'sometimes|string|max:255',
            'email'            => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'nik'              => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($userId),
            ],
            'telepon'          => 'sometimes|nullable|string|max:20',
            'alamat'           => 'sometimes|nullable|string',
            'foto_profil'      => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:5120',

            // Password change (opsional)
            'current_password' => 'required_with:new_password|string',
            'new_password'     => 'sometimes|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                 => 'Email sudah digunakan oleh user lain',
            'nik.unique'                   => 'NIK sudah digunakan oleh user lain',
            'current_password.required_with' => 'Password lama wajib diisi untuk mengganti password',
            'new_password.min'             => 'Password baru minimal 8 karakter',
            'new_password.confirmed'       => 'Konfirmasi password baru tidak cocok',
        ];
    }
}