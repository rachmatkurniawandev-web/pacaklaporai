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
        Schema::create('dinas', function (Blueprint $table) {
            // Primary Key
            $table->id();
            // ID unik untuk setiap dinas, auto-increment

            // Kolom Data Dinas
            $table->string('nama');
            // Nama dinas, misal: "Dinas Kebersihan"
            // string = VARCHAR(191) - maksimal 191 karakter

            $table->string('kode', 10)->unique();
            // Kode singkat dinas, misal: "DLH", "DISHUB"
            // string('kode', 10) = VARCHAR(10) - maksimal 10 karakter
            // unique() = tidak boleh ada kode yang sama (unik)

            $table->string('email')->nullable();
            // Email kontak dinas, misal: "kebersihan@palembang.go.id"
            // nullable() = boleh kosong (optional)

            $table->string('telepon', 20)->nullable();
            // Nomor telepon dinas, misal: "0711-123456"
            // 20 karakter cukup untuk nomor telepon Indonesia

            $table->text('alamat')->nullable();
            // Alamat kantor dinas
            // text = TEXT type - untuk teks panjang

            $table->text('deskripsi')->nullable();
            // Deskripsi singkat tentang dinas & tugasnya

            $table->boolean('is_active')->default(true);
            // Status aktif/nonaktif dinas
            // boolean = TINYINT(1) - nilai 0 atau 1
            // default(true) = default value-nya true (aktif)

            // Timestamps
            $table->timestamps();
            // Otomatis bikin kolom:
            // - created_at (kapan data dibuat)
            // - updated_at (kapan data terakhir diupdate)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dinas');
    }
};
