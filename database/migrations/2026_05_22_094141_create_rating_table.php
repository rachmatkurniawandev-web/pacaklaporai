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
        Schema::create('rating', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->id();
            
            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignId('laporan_id')->constrained('laporan')->onDelete('cascade');
            // ID laporan yang diberi rating
            // onDelete('cascade') = kalau laporan dihapus, rating ikut terhapus
            // 1 laporan = maksimal 1 rating (dari si pembuat laporan)
            
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // ID user yang kasih rating (warga yang bikin laporan)
            // onDelete('cascade') = kalau user dihapus, rating ikut terhapus
            
            // ============================================
            // DATA RATING
            // ============================================
            $table->tinyInteger('nilai_rating')->unsigned();
            // Rating bintang 1-5
            // tinyInteger = TINYINT (angka kecil -128 sampai 127)
            // unsigned() = cuma angka positif (0-255)
            // Nanti di validasi Laravel: min:1, max:5
            // Misal: 5 = sangat puas, 1 = sangat tidak puas
            
            $table->text('komentar')->nullable();
            // Review/komentar dari warga
            // Misal: "Cepat ditangani, sampah langsung diangkut. Terima kasih!"
            // nullable() = boleh kosong (warga bisa kasih rating tanpa komentar)
            
            // ============================================
            // ASPEK RATING (Opsional, Detail)
            // ============================================
            $table->tinyInteger('kecepatan_respon')->unsigned()->nullable();
            // Rating khusus untuk kecepatan respon (1-5)
            // "Seberapa cepat dinas merespon laporan?"
            // nullable() = opsional, bisa ga diisi
            
            $table->tinyInteger('kualitas_penanganan')->unsigned()->nullable();
            // Rating khusus untuk kualitas penanganan (1-5)
            // "Seberapa bagus penanganan masalahnya?"
            
            $table->tinyInteger('sikap_petugas')->unsigned()->nullable();
            // Rating khusus untuk sikap petugas (1-5)
            // "Seberapa ramah petugasnya?"
            
            // ============================================
            // METADATA
            // ============================================
            $table->boolean('is_anonymous')->default(false);
            // Apakah review ini anonim?
            // Kalau true, nama warga ga ditampilkan di publik
            // default(false) = default tampilkan nama
            
            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();
            // created_at = kapan rating dibuat
            // updated_at = kapan rating diupdate (kalau warga edit review)
            
            // ============================================
            // UNIQUE CONSTRAINT
            // ============================================
            $table->unique(['laporan_id', 'user_id']);
            // 1 user cuma bisa kasih 1 rating per laporan
            // Ga bisa rating 2x untuk laporan yang sama
            // Kombinasi laporan_id + user_id harus unik
            
            // ============================================
            // INDEXES
            // ============================================
            $table->index('nilai_rating');
            // Index untuk query statistik: "Berapa rata-rata rating semua laporan?"
            
            $table->index('created_at');
            // Index untuk query: "Rating terbaru apa aja?"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating');
    }
};