<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dinas extends Model
{
    

    protected $table = 'dinas';
    
    protected $fillable = [
        'nama',
        'kode',
        'email',
        'telepon',
        'alamat',
        'deskripsi',
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