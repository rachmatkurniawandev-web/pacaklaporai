<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'laporan_id',
        'judul',
        'pesan',
        'tipe',
        'action_url',
        'icon',
        'is_read',
        'read_at',
        'is_pushed',
        'pushed_at',
        'fcm_message_id',
    ];

    protected $casts = [
        'is_read'   => 'boolean',
        'is_pushed' => 'boolean',
        'read_at'   => 'datetime',
        'pushed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }
}