<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IplTagihan extends Model
{
    use HasFactory;

    protected $table = 'ipl_tagihan';

    protected $fillable = [
        'warga_id',
        'jenis',
        'bulan',
        'tahun',
        'nominal',
        'denda',
        'status',
        'jatuh_tempo',
        'tanggal_bayar',
        'reminder_terakhir',
        'keterangan',
    ];

    protected $casts = [
        'jatuh_tempo' => 'date',
        'tanggal_bayar' => 'date',
        'reminder_terakhir' => 'date',
        'nominal' => 'decimal:2',
        'denda' => 'decimal:2',
    ];

    protected static $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'tagihan_id');
    }

    public function getNamaBulanAttribute(): string
    {
        return self::$namaBulan[$this->bulan] ?? '';
    }

    public function getTotalTagihanAttribute(): float
    {
        return $this->nominal + $this->denda;
    }

    public function isTerlambat(): bool
    {
        return now()->gt($this->jatuh_tempo) && $this->status !== 'sudah_bayar';
    }

    public function scopeBelumBayar($query)
    {
        return $query->whereIn('status', ['belum_bayar', 'terlambat']);
    }

    public function scopeBulanIni($query)
    {
        return $query->where('bulan', now()->month)->where('tahun', now()->year);
    }
}
