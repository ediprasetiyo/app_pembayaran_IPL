<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blok extends Model
{
    use HasFactory;

    protected $table = 'bloks';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'is_active',
        'jumlah_rumah',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'jumlah_rumah' => 'integer',
    ];

    /**
     * Warga di blok ini (lewat kolom blok string di tabel warga).
     * Match by kode untuk backward compat dengan data lama.
     */
    public function warga()
    {
        return $this->hasMany(Warga::class, 'blok', 'kode');
    }
}
