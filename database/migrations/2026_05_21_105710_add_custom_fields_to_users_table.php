<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambah kolom setelah kolom 'email'
            $table->after('email', function (Blueprint $table) {
                
                $table->enum('role', ['warga', 'admin', 'petugas'])->default('warga');
                // Role user: warga (user biasa), admin (super admin), petugas (staff dinas)
                // enum = kolom dengan pilihan fixed values
                // default('warga') = user baru otomatis jadi warga
                
                $table->string('nik', 16)->nullable()->unique();
                // NIK (Nomor Induk Kependudukan) - 16 digit
                // nullable = boleh kosong (karena admin/petugas mungkin ga perlu NIK)
                // unique = NIK harus unik (1 NIK = 1 akun)
                
                $table->string('telepon', 20)->nullable();
                // Nomor HP/WhatsApp warga
                
                $table->text('alamat')->nullable();
                // Alamat lengkap warga
                
                $table->string('foto_profil')->nullable();
                // URL foto profil (nanti upload ke Cloudinary)
                
                $table->boolean('is_active')->default(true);
                // Status aktif/banned
                // Kalau user spam, admin bisa nonaktifkan
            });
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom kalau rollback
            $table->dropColumn([
                'role',
                'nik',
                'telepon',
                'alamat',
                'foto_profil',
                'is_active'
            ]);
        });
    }
};