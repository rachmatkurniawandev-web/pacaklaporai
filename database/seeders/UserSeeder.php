<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin Palembang',
            'email' => 'admin@palembang.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'nik' => '1234567890123401',
            'telepon' => '+62812345601',
            'alamat' => 'Jl. Sultan Mahmud Badaruddin II',
            'foto_profil' => null,
            'is_active' => true,
        ]);

        // Dummy users (warga)
        $users = [
            [
                'name' => 'Rachmat Kurniawan',
                'email' => 'rachmat@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123402',
                'telepon' => '+62812345602',
                'alamat' => 'Jl. Sudirman No. 123',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123403',
                'telepon' => '+62812345603',
                'alamat' => 'Jl. Merdeka No. 45',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123404',
                'telepon' => '+62812345604',
                'alamat' => 'Jl. Ahmad Yani No. 67',
            ],
            [
                'name' => 'Ahmad Wijaya',
                'email' => 'ahmad@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123405',
                'telepon' => '+62812345605',
                'alamat' => 'Jl. Kapten Rivai No. 89',
            ],
            [
                'name' => 'Maya Kusuma',
                'email' => 'maya@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123406',
                'telepon' => '+62812345606',
                'alamat' => 'Jl. Hasanuddin No. 101',
            ],
            [
                'name' => 'Rinto Harahap',
                'email' => 'rinto@test.com',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'nik' => '1234567890123407',
                'telepon' => '+62812345607',
                'alamat' => 'Jl. Gatot Subroto No. 123',
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123408',
                'telepon' => '+62812345608',
                'alamat' => 'Jl. Diponegoro No. 145',
            ],
            [
                'name' => 'Hendra Gunawan',
                'email' => 'hendra@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123409',
                'telepon' => '+62812345609',
                'alamat' => 'Jl. Teuku Umar No. 167',
            ],
            [
                'name' => 'Lia Amelia',
                'email' => 'lia@test.com',
                'password' => Hash::make('password123'),
                'role' => 'warga',
                'nik' => '1234567890123410',
                'telepon' => '+62812345610',
                'alamat' => 'Jl. Veteran No. 189',
            ],
        ];

        foreach ($users as $user) {
            User::create(array_merge($user, [
                'foto_profil' => null,
                'is_active' => true,
            ]));
        }
    }
}
