<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'kategori',
        'keterangan',
        'nominal',
        'sumber_dana',
        'tanggal',
        'bukti_url',
        'created_by',
        'warga_id',
        'catatan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal' => 'date',
        'created_by' => 'integer',
        'warga_id' => 'integer',
    ];

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }
}
