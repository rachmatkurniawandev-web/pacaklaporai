<?php

namespace Database\Seeders;

use App\Models\Dinas;
use Illuminate\Database\Seeder;

class DinasSeeder extends Seeder
{
    public function run(): void
    {
        $dinas = [
            [
                'nama' => 'Dinas Kebersihan dan Pertamanan',
                'kode' => 'DKP',
                'email' => 'dkp@palembang.go.id',
                'telepon' => '0711-1234567',
                'alamat' => 'Jl. Sultan Mahmud Badaruddin II No. 1',
                'deskripsi' => 'Mengelola kebersihan dan taman kota',
                'is_active' => true,
            ],
            [
                'nama' => 'Dinas Pekerjaan Umum',
                'kode' => 'DPU',
                'email' => 'dpu@palembang.go.id',
                'telepon' => '0711-2345678',
                'alamat' => 'Jl. Jend. Sudirman No. 10',
                'deskripsi' => 'Mengelola infrastruktur jalan dan bangunan',
                'is_active' => true,
            ],
            [
                'nama' => 'Dinas Sosial',
                'kode' => 'DINSOS',
                'email' => 'dinsos@palembang.go.id',
                'telepon' => '0711-3456789',
                'alamat' => 'Jl. Imam Bonjol No. 5',
                'deskripsi' => 'Mengelola kesejahteraan sosial masyarakat',
                'is_active' => true,
            ],
        ];

        foreach ($dinas as $item) {
            Dinas::create($item);
        }
    }
}