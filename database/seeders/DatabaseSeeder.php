<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run seeders in order
        $this->call([
            DinasSeeder::class,
            KategoriSeeder::class,
            UserSeeder::class,
            MasterDataDemoSeeder::class,
            LaporanDemoSeeder::class,
        ]);
    }
}