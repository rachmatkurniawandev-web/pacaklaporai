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
        Schema::create('laporan_foto', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->id();
            // ID unik setiap foto
            
            // ============================================
            // FOREIGN KEY - Relasi ke Tabel Laporan
            // ============================================
            $table->foreignId('laporan_id')->constrained('laporan')->onDelete('cascade');
            // ID laporan yang punya foto ini
            // foreignId = BIGINT UNSIGNED
            // constrained('laporan') = foreign key ke tabel laporan
            // onDelete('cascade') = kalau laporan dihapus, semua foto ikut terhapus
            // Misal: laporan ID 5 dihapus, semua foto dengan laporan_id=5 otomatis terhapus
            
            // ============================================
            // DATA FOTO
            // ============================================
            $table->string('url');
            // URL lengkap foto yang diupload ke Cloudinary
            // Misal: "https://res.cloudinary.com/pacaklaporai/image/upload/v1234567890/abc123.jpg"
            // string = VARCHAR(191)
            
            $table->string('public_id')->nullable();
            // Public ID dari Cloudinary (untuk delete foto nanti)
            // Misal: "pacaklaporai/laporan/abc123"
            // Berguna saat mau delete foto dari Cloudinary
            // nullable() = boleh kosong (kalau ga pakai Cloudinary)
            
            // ============================================
            // METADATA FOTO
            // ============================================
            $table->integer('urutan')->default(1);
            // Urutan foto dalam laporan (foto 1, 2, 3, ...)
            // Foto urutan 1 = foto utama/thumbnail
            // integer = INT (nilai angka bulat)
            // default(1) = kalau ga diisi, otomatis 1
            
            $table->boolean('is_primary')->default(false);
            // Apakah ini foto utama/thumbnail?
            // Hanya 1 foto per laporan yang is_primary = true
            // Foto ini yang muncul di list laporan
            // boolean = TINYINT(1) - nilai 0 atau 1
            // default(false) = default bukan foto utama
            
            $table->string('keterangan')->nullable();
            // Keterangan/caption opsional untuk foto
            // Misal: "Foto dari depan", "Foto jarak jauh", "Foto close-up"
            // nullable() = boleh kosong
            
            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();
            // created_at = kapan foto diupload
            // updated_at = kapan foto terakhir diupdate (misal ganti keterangan)
            
            // ============================================
            // INDEX untuk Performance
            // ============================================
            $table->index(['laporan_id', 'urutan']);
            // Composite index untuk query: "ambil semua foto laporan X, sorted by urutan"
            // Query ini akan SANGAT SERING dipakai (setiap buka detail laporan)
            // Tanpa index, query lambat kalau data banyak
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_foto');
        // Kalau rollback, hapus tabel laporan_foto
    }
};