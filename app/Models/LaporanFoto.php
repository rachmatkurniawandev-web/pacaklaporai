<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanFoto extends Model
{
    protected $table = 'laporan_foto';

    protected $fillable = [
        'laporan_id',
        'url',
        'public_id',
        'urutan',
        'is_primary',
        'keterangan',
        // Enhancement columns (baru)
        'enhanced_url',
        'enhanced_public_id',
        'brightness',
        'contrast',
        'sharpness',
        'is_enhanced',
        'enhanced_by',
        'enhanced_at',
    ];

    protected $casts = [
        'is_primary'  => 'boolean',
        'is_enhanced' => 'boolean',
        'urutan'      => 'integer',
        'brightness'  => 'integer',
        'contrast'    => 'integer',
        'sharpness'   => 'integer',
        'enhanced_at' => 'datetime',
    ];

    // Relasi ke laporan
    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }

    // Relasi ke admin yang melakukan enhancement
    public function enhancedBy()
    {
        return $this->belongsTo(User::class, 'enhanced_by');
    }
}