<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            [
                'nama' => 'Kebersihan',
                'deskripsi' => 'Laporan terkait kebersihan kota',
                'icon' => '🧹',
                'warna' => '#10B981',
                'is_active' => true,
            ],
            [
                'nama' => 'Infrastruktur',
                'deskripsi' => 'Laporan kerusakan jalan dan fasilitas publik',
                'icon' => '🏗️',
                'warna' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'nama' => 'Keamanan',
                'deskripsi' => 'Laporan terkait keamanan dan ketertiban',
                'icon' => '🚨',
                'warna' => '#EF4444',
                'is_active' => true,
            ],
            [
                'nama' => 'Kesehatan',
                'deskripsi' => 'Laporan terkait kesehatan publik',
                'icon' => '⚕️',
                'warna' => '#F59E0B',
                'is_active' => true,
            ],
            [
                'nama' => 'Lainnya',
                'deskripsi' => 'Laporan dengan kategori lainnya',
                'icon' => '📋',
                'warna' => '#6B7280',
                'is_active' => true,
            ],
        ];

        foreach ($kategori as $item) {
            Kategori::create($item);
        }
    }
}