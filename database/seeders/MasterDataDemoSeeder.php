<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dinas;
use App\Models\Kategori;

class MasterDataDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ===== TAMBAH DINAS =====
        $dinasList = [
            [
                'nama' => 'Dinas Penerangan Jalan Umum',
                'kode' => 'PJU',
                'email' => 'pju@palembang.go.id',
                'telepon' => '0711-330001',
                'alamat' => 'Jl. Merdeka No. 1, Palembang',
                'deskripsi' => 'Mengelola lampu jalan dan penerangan publik',
                'is_active' => true,
            ],
            [
                'nama' => 'Badan Penanggulangan Bencana Daerah',
                'kode' => 'BPBD',
                'email' => 'bpbd@palembang.go.id',
                'telepon' => '0711-330002',
                'alamat' => 'Jl. Kapten A. Rivai, Palembang',
                'deskripsi' => 'Penanganan bencana dan kondisi darurat',
                'is_active' => true,
            ],
            [
                'nama' => 'Dinas Perhubungan',
                'kode' => 'DISHUB',
                'email' => 'dishub@palembang.go.id',
                'telepon' => '0711-330003',
                'alamat' => 'Jl. Demang Lebar Daun, Palembang',
                'deskripsi' => 'Lalu lintas, transportasi publik, dan rambu jalan',
                'is_active' => true,
            ],
        ];

        foreach ($dinasList as $data) {
            Dinas::firstOrCreate(
                ['kode' => $data['kode']], // cek by kode (unique)
                $data
            );
        }

        // ===== TAMBAH KATEGORI =====
        $kategoriList = [
            [
                'nama' => 'Lampu Jalan',
                'deskripsi' => 'Lampu penerangan jalan rusak atau mati',
                'icon' => 'lightbulb',
                'warna' => '#FFC107',
                'is_active' => true,
            ],
            [
                'nama' => 'Banjir',
                'deskripsi' => 'Genangan air atau banjir',
                'icon' => 'water',
                'warna' => '#2196F3',
                'is_active' => true,
            ],
            [
                'nama' => 'Pohon Tumbang',
                'deskripsi' => 'Pohon tumbang atau dahan patah',
                'icon' => 'tree',
                'warna' => '#4CAF50',
                'is_active' => true,
            ],
            [
                'nama' => 'Lainnya',
                'deskripsi' => 'Laporan masalah publik lainnya',
                'icon' => 'help-circle',
                'warna' => '#9E9E9E',
                'is_active' => true,
            ],
        ];

        foreach ($kategoriList as $data) {
            Kategori::firstOrCreate(
                ['nama' => $data['nama']],
                $data
            );
        }

        $this->command->info('Master data demo seeded successfully.');
    }
}