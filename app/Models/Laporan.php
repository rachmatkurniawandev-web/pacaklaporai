<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laporan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laporan'; // tabel singular, perlu eksplisit

    protected $fillable = [
        'nomor_tiket',
        'user_id',
        'kategori_id',
        'dinas_id',
        'judul',
        'deskripsi',
        'lokasi',
        'latitude',
        'longitude',
        'status',
        'prioritas',
        'tanggal_kejadian',
        'tanggal_selesai',
        'catatan_petugas',
        'alasan_ditolak',
        'ai_confidence',
        'ai_metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ai_confidence' => 'decimal:2',
        'tanggal_kejadian' => 'date',
        'tanggal_selesai' => 'datetime',
    ];

    // ===== Relasi =====

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function dinas()
    {
        return $this->belongsTo(Dinas::class);
    }

    public function fotos()
    {
        return $this->hasMany(LaporanFoto::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusHistory::class);
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    // ===== Helper: generate nomor tiket unik =====

    public static function generateNomorTiket(): string
    {
        do {
            // Format: LAP-XJ29KQ (6 karakter random huruf besar + angka)
            $kode = 'LAP-'.strtoupper(substr(str_replace(['0', 'O', '1', 'I'], '', md5(uniqid())), 0, 6));
        } while (self::where('nomor_tiket', $kode)->exists());

        return $kode;
    }

    // ===== Helper: validasi transisi status =====

    public static function transisiValid(): array
    {
        return [
            'pending' => ['verifikasi', 'ditolak'],
            'verifikasi' => ['diproses', 'ditolak'],
            'diproses' => ['selesai', 'ditolak'],
            'selesai' => [], // status final
            'ditolak' => [], // status final
        ];
    }

    public function bisaTransisiKe(string $statusBaru): bool
    {
        $transisi = self::transisiValid();

        return in_array($statusBaru, $transisi[$this->status] ?? []);
    }

    
}
