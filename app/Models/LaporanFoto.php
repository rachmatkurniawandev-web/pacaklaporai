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
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'urutan'     => 'integer',
    ];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }
}