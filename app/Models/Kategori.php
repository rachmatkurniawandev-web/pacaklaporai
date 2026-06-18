<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    

    protected $table = 'kategori';

    protected $fillable = [
        'nama',
        'deskripsi',
        'icon',
        'warna',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function laporan()
    {
        return $this->hasMany(Laporan::class);
    }
}