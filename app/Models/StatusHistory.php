<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $table = 'status_history';

    // Tidak pakai updated_at, hanya created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'laporan_id',
        'user_id',
        'status_dari',
        'status_ke',
        'keterangan',
        'ip_address',
        'user_agent',
    ];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}