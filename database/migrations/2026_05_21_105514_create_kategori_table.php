<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Kolom Data Kategori
            $table->string('nama');
            // Nama kategori, misal: "Sampah", "Jalan Rusak"

            $table->text('deskripsi')->nullable();
            // Deskripsi kategori

            $table->string('icon', 50)->nullable();
            // Nama icon untuk UI (misal: "trash", "road")
            // Ini string pendek aja, nanti icon sebenarnya dari icon library

            $table->string('warna', 7)->nullable();
            // Kode warna hex, misal: "#FF5733" (6 char + 1 untuk #)
            // Untuk display warna kategori di UI

            $table->boolean('is_active')->default(true);
            // Status aktif/nonaktif

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
