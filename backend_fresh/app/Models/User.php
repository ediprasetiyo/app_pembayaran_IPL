<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'tanggal_lahir',
        'tempat_lahir',
        'email',
        'password',
        'role',
        'blok_id',
        'fcm_token',
        'language',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'tanggal_lahir' => 'date',
    ];

    public function warga()
    {
        return $this->hasOne(Warga::class);
    }

    public function blok()
    {
        return $this->belongsTo(Blok::class);
    }

    /**
     * Cek apakah user ini bisa akses semua blok (super_admin) atau scope ke 1 blok.
     */
    public function canAccessAllBloks(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Dapatkan kode blok untuk filter — kalau super_admin, return null (semua).
     * Selain itu, return kode blok user dari relasi Blok.
     */
    public function getScopedBlokKode(): ?string
    {
        if ($this->canAccessAllBloks()) return null;
        return $this->blok?->kode;
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'bendahara', 'humas']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isBendahara(): bool
    {
        return $this->role === 'bendahara';
    }

    /**
     * Boleh ubah status pembayaran manual (super_admin & bendahara)
     */
    public function canManageTagihan(): bool
    {
        return in_array($this->role, ['super_admin', 'bendahara']);
    }
}
