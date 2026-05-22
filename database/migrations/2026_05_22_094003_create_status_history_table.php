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
        Schema::create('status_history', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->id();
            // ID unik setiap record history
            
            // ============================================
            // FOREIGN KEYS
            // ============================================
            $table->foreignId('laporan_id')->constrained('laporan')->onDelete('cascade');
            // ID laporan yang berubah statusnya
            // onDelete('cascade') = kalau laporan dihapus, history ikut terhapus
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            // ID user yang melakukan perubahan status (admin/petugas)
            // nullable() = boleh NULL (kalau system otomatis yang ubah, misal AI)
            // onDelete('set null') = kalau user dihapus, user_id jadi NULL (history tetap ada)
            
            // ============================================
            // DATA STATUS
            // ============================================
            $table->enum('status_dari', [
                'pending',
                'verifikasi',
                'diproses',
                'selesai',
                'ditolak'
            ])->nullable();
            // Status SEBELUM perubahan
            // nullable() = untuk record pertama (status awal), status_dari = NULL
            // Misal: pending → verifikasi
            //        status_dari = 'pending', status_ke = 'verifikasi'
            
            $table->enum('status_ke', [
                'pending',
                'verifikasi',
                'diproses',
                'selesai',
                'ditolak'
            ]);
            // Status SETELAH perubahan
            // Ini status baru yang diterapkan
            
            // ============================================
            // KETERANGAN
            // ============================================
            $table->text('keterangan')->nullable();
            // Catatan/alasan perubahan status
            // Misal: 
            // - "Laporan valid, diteruskan ke Dinas Kebersihan"
            // - "Petugas sudah di lokasi, sedang menangani"
            // - "Sampah sudah diangkut, masalah selesai"
            // nullable() = boleh kosong
            
            // ============================================
            // METADATA
            // ============================================
            $table->string('ip_address', 45)->nullable();
            // IP address user yang melakukan perubahan
            // Untuk audit trail & security
            // 45 karakter cukup untuk IPv6 (yang paling panjang)
            // nullable() = kalau system yang ubah, ga ada IP
            
            $table->string('user_agent')->nullable();
            // Browser/device info yang dipakai
            // Misal: "Mozilla/5.0 (Android 13; Mobile) ..."
            // Berguna untuk tracking: "Petugas update dari HP atau laptop?"
            
            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamp('created_at')->useCurrent();
            // Kapan perubahan status terjadi
            // useCurrent() = otomatis isi dengan waktu sekarang
            // PENTING: kita cuma butuh created_at (ga perlu updated_at)
            // Karena history ga pernah di-update, cuma insert!
            
            // ============================================
            // INDEXES
            // ============================================
            $table->index(['laporan_id', 'created_at']);
            // Index untuk query: "ambil timeline status laporan X, sorted by waktu"
            // Query ini sangat sering (setiap buka detail laporan)
            
            $table->index('status_ke');
            // Index untuk query statistik: "Berapa laporan yang jadi 'selesai' hari ini?"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_history');
    }
};