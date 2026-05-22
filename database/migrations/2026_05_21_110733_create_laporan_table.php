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
        Schema::create('laporan', function (Blueprint $table) {
            // ============================================
            // PRIMARY KEY
            // ============================================
            $table->id();
            // ID unik setiap laporan

            // ============================================
            // NOMOR TIKET
            // ============================================
            $table->string('nomor_tiket', 20)->unique();
            // Nomor tiket unik untuk tracking, misal: "LAP-2024-00001"
            // Ini yang akan dilihat warga untuk cek status laporan
            // unique() = tidak boleh ada nomor tiket yang sama

            // ============================================
            // FOREIGN KEYS (Relasi ke Tabel Lain)
            // ============================================
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // ID user yang buat laporan (warga)
            // foreignId('user_id') = bikin kolom BIGINT UNSIGNED
            // constrained('users') = ini foreign key ke tabel users
            // onDelete('cascade') = kalau user dihapus, laporan ikut terhapus

            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->onDelete('set null');
            // ID kategori laporan (misal: kategori "Sampah")
            // nullable() = boleh kosong (sementara, sambil tunggu AI klasifikasi)
            // onDelete('set null') = kalau kategori dihapus, kolom ini jadi NULL (laporan tetap ada)

            $table->foreignId('dinas_id')->nullable()->constrained('dinas')->onDelete('set null');
            // ID dinas yang handle laporan (misal: Dinas Kebersihan)
            // nullable() = boleh kosong (sementara, sambil tunggu routing)
            // onDelete('set null') = kalau dinas dihapus, kolom ini jadi NULL

            // ============================================
            // DATA LAPORAN
            // ============================================
            $table->string('judul');
            // Judul singkat laporan, misal: "Sampah menumpuk di Jl. Sudirman"

            $table->text('deskripsi');
            // Deskripsi detail laporan
            // Misal: "Sampah sudah menumpuk sejak 3 hari lalu, bau menyengat"

            // ============================================
            // LOKASI
            // ============================================
            $table->text('lokasi');
            // Alamat text lokasi kejadian
            // Misal: "Jl. Sudirman No. 123, Ilir Barat I, Palembang"

            $table->decimal('latitude', 10, 8)->nullable();
            // Koordinat latitude (untuk peta)
            // decimal(10, 8) = maksimal 10 digit, 8 digit desimal
            // Contoh: -2.98765432 (koordinat Palembang)
            // nullable() = boleh kosong kalau GPS tidak aktif

            $table->decimal('longitude', 11, 8)->nullable();
            // Koordinat longitude (untuk peta)
            // decimal(11, 8) = maksimal 11 digit, 8 digit desimal
            // Contoh: 104.75123456 (koordinat Palembang)

            // ============================================
            // STATUS & PRIORITAS
            // ============================================
            $table->enum('status', [
                'pending',      // Laporan baru masuk, belum diproses
                'verifikasi',   // Sedang diverifikasi admin/AI
                'diproses',     // Sedang ditangani dinas
                'selesai',      // Sudah selesai ditangani
                'ditolak',       // Laporan ditolak (spam/tidak valid)
            ])->default('pending');
            // Status alur laporan
            // enum = kolom dengan nilai fixed (tidak bisa isi sembarangan)
            // default('pending') = laporan baru otomatis status pending

            $table->enum('prioritas', [
                'rendah',       // Tidak urgent (misal: cat tembok kusam)
                'sedang',       // Cukup penting (misal: lampu jalan mati)
                'tinggi',       // Urgent (misal: jalan rusak parah)
                'darurat',       // Sangat urgent (misal: pohon tumbang tutup jalan)
            ])->default('sedang');
            // Tingkat urgensi laporan
            // Nanti AI atau admin yang tentukan prioritas

            // ============================================
            // TANGGAL
            // ============================================
            $table->date('tanggal_kejadian')->nullable();
            // Kapan kejadian yang dilaporkan
            // Misal: warga lapor hari ini, tapi kejadian 3 hari lalu
            // date = format YYYY-MM-DD (misal: 2024-05-21)

            $table->timestamp('tanggal_selesai')->nullable();
            // Kapan laporan selesai ditangani
            // timestamp = format datetime lengkap (2024-05-21 14:30:00)
            // nullable() = kosong sampai status jadi 'selesai'

            // ============================================
            // CATATAN INTERNAL
            // ============================================
            $table->text('catatan_petugas')->nullable();
            // Catatan dari petugas dinas saat memproses
            // Misal: "Sampah sudah diangkut pada tanggal 21 Mei 2024"

            $table->text('alasan_ditolak')->nullable();
            // Alasan kalau laporan ditolak
            // Misal: "Laporan duplikat" atau "Foto tidak jelas"

            // ============================================
            // AI CLASSIFICATION
            // ============================================
            $table->decimal('ai_confidence', 5, 2)->nullable();
            // Confidence score dari AI (0.00 - 100.00)
            // Misal: 95.75 artinya AI 95.75% yakin ini kategori "Sampah"
            // decimal(5, 2) = maksimal 5 digit, 2 desimal (misal: 100.00)

            $table->text('ai_metadata')->nullable();
            // Simpan data JSON dari AI dalam format text
            // Laravel akan otomatis handle JSON encode/decode
            // Contoh isi: {"model":"MobileNetV2","confidence":95.75}

            // ============================================
            // TIMESTAMPS
            // ============================================
            $table->timestamps();
            // created_at = kapan laporan dibuat
            // updated_at = kapan laporan terakhir diupdate

            $table->softDeletes();
            // deleted_at = untuk soft delete (hapus tanpa benar-benar hapus dari database)
            // Kalau laporan di-delete, data tetap ada tapi ada timestamp deleted_at
            // Berguna untuk audit trail & recovery

            // ============================================
            // INDEXES (untuk Performance)
            // ============================================
            $table->index('nomor_tiket');
            // Index untuk pencarian by nomor tiket (warga cek status)
            // Tanpa index, query akan lambat kalau data banyak

            $table->index('status');
            // Index untuk filter by status (admin lihat laporan pending)

            $table->index(['user_id', 'created_at']);
            // Composite index untuk query "laporan user X, sorted by tanggal"
            // Berguna untuk halaman "Laporan Saya" di mobile app

            $table->index(['dinas_id', 'status']);
            // Composite index untuk query "laporan dinas X dengan status Y"
            // Berguna untuk dashboard dinas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
