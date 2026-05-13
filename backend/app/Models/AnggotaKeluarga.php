<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeluarga extends Model
{
    use HasFactory;

    protected $table = 'anggota_keluarga';

    protected $fillable = [
        'warga_id',
        'nama',
        'hubungan',
        'jenis_kelamin',
        'tanggal_lahir',
        'nik',
        'pekerjaan',
        'pendidikan',
        'agama',
        'status_perkawinan',
        'kewarganegaraan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function getUmurAttribute(): ?int
    {
        return $this->tanggal_lahir
            ? $this->tanggal_lahir->age
            : null;
    }
}
