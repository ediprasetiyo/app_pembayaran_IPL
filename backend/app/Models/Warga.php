<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warga extends Model
{
    use HasFactory;

    protected $table = 'warga';

    protected $fillable = [
        'user_id',
        'nomor_rumah',
        'blok',
        'rt',
        'rw',
        'status_hunian',
        'tanggal_pindah',
        'nik',
        'alamat_asal',
        'is_active',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pindah' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anggotaKeluarga()
    {
        return $this->hasMany(AnggotaKeluarga::class);
    }

    public function tagihan()
    {
        return $this->hasMany(IplTagihan::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function getAlamatLengkapAttribute(): string
    {
        return "Blok {$this->blok} No. {$this->nomor_rumah}";
    }

    public function getTunggakanAttribute(): int
    {
        return $this->tagihan()
            ->whereIn('status', ['belum_bayar', 'terlambat'])
            ->count();
    }
}
