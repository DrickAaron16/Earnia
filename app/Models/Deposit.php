<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'provider',
        'payment_method',
        'external_reference',
        'amount',
        'fee',
        'status',
        'metadata',
        'expires_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(WalletTransaction::class, 'transactable');
    }
}

