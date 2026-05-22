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
        Schema::create('notifikasi', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->id();
            
            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // ID user yang menerima notifikasi
            // onDelete('cascade') = kalau user dihapus, notifikasi ikut terhapus
            
            $table->foreignId('laporan_id')->nullable()->constrained('laporan')->onDelete('cascade');
            // ID laporan terkait (kalau notifikasi tentang laporan tertentu)
            // nullable() = boleh NULL (untuk notifikasi umum, misal: "Selamat datang!")
            // onDelete('cascade') = kalau laporan dihapus, notifikasi ikut terhapus
            
            // ============================================
            // KONTEN NOTIFIKASI
            // ============================================
            $table->string('judul');
            // Judul/title notifikasi
            // Misal: "Laporan Sedang Diproses"
            // Ini yang muncul di notifikasi push (bold/tebal)
            
            $table->text('pesan');
            // Isi pesan notifikasi lengkap
            // Misal: "Laporan #LAP-2024-00123 tentang Sampah Menumpuk sedang ditangani oleh Dinas Kebersihan"
            
            $table->enum('tipe', [
                'status_update',    // Perubahan status laporan
                'laporan_selesai',  // Laporan sudah selesai
                'komentar',         // Ada komentar baru
                'rating_reminder',  // Reminder untuk kasih rating
                'sistem',           // Notifikasi sistem umum
                'broadcast'         // Broadcast ke banyak user
            ])->default('sistem');
            // Tipe/kategori notifikasi
            // Berguna untuk filter & styling di UI
            // Misal: tipe 'laporan_selesai' pakai icon centang hijau ✅
            
            // ============================================
            // METADATA
            // ============================================
            $table->string('action_url')->nullable();
            // URL/route tujuan kalau notifikasi di-klik
            // Misal: "/laporan/123" → buka detail laporan
            // nullable() = boleh NULL (kalau notifikasi cuma info, ga ada action)
            
            $table->string('icon')->nullable();
            // Nama icon untuk notifikasi
            // Misal: "check-circle" untuk laporan selesai
            // "alert-triangle" untuk laporan ditolak
            // nullable() = pakai icon default kalau ga diisi
            
            // ============================================
            // STATUS NOTIFIKASI
            // ============================================
            $table->boolean('is_read')->default(false);
            // Sudah dibaca belum?
            // false = belum dibaca (tampil badge merah di UI)
            // true = sudah dibaca
            // default(false) = notifikasi baru otomatis belum dibaca
            
            $table->timestamp('read_at')->nullable();
            // Kapan notifikasi dibaca?
            // NULL kalau belum dibaca
            // Diisi timestamp saat user klik notifikasi
            
            // ============================================
            // PUSH NOTIFICATION (Firebase FCM)
            // ============================================
            $table->boolean('is_pushed')->default(false);
            // Sudah dikirim push notification belum?
            // false = belum dikirim
            // true = sudah dikirim ke Firebase FCM
            
            $table->timestamp('pushed_at')->nullable();
            // Kapan push notification dikirim?
            // NULL kalau belum/gagal dikirim
            
            $table->string('fcm_message_id')->nullable();
            // Message ID dari Firebase (untuk tracking)
            // Berguna kalau mau cek status pengiriman di Firebase
            
            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();
            // created_at = kapan notifikasi dibuat
            // updated_at = kapan notifikasi diupdate (misal: dari unread jadi read)
            
            // ============================================
            // INDEXES
            // ============================================
            $table->index(['user_id', 'is_read', 'created_at']);
            // Composite index untuk query: "notifikasi belum dibaca user X, sorted by terbaru"
            // Query ini SANGAT SERING (setiap buka app, cek notifikasi baru)
            
            $table->index('tipe');
            // Index untuk filter by tipe: "ambil semua notifikasi tipe 'status_update'"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};