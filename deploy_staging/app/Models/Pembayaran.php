<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'tagihan_id',
        'warga_id',
        'order_id',
        'nominal',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'midtrans_snap_token',
        'midtrans_redirect_url',
        'status',
        'midtrans_response',
        'catatan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'midtrans_response' => 'array',
    ];

    public function tagihan()
    {
        return $this->belongsTo(IplTagihan::class, 'tagihan_id');
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
