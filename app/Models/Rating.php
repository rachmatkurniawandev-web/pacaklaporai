<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'rating';

    protected $fillable = [
        'laporan_id',
        'user_id',
        'nilai_rating',
        'komentar',
        'kecepatan_respon',
        'kualitas_penanganan',
        'sikap_petugas',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous'         => 'boolean',
        'nilai_rating'         => 'integer',
        'kecepatan_respon'     => 'integer',
        'kualitas_penanganan'  => 'integer',
        'sikap_petugas'        => 'integer',
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