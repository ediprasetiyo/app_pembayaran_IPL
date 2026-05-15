<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan';

    protected $fillable = [
        'warga_id',
        'judul',
        'deskripsi',
        'kategori',
        'foto',
        'status',
        'prioritas',
        'tanggal_selesai',
        'keterangan_admin',
        'ditangani_oleh',
    ];

    protected $casts = [
        'foto' => 'array',
        'tanggal_selesai' => 'datetime',
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }
}
