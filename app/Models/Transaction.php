<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'reference',
        'payment_method',
        'payment_provider',
        'payment_reference',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'TXN' . strtoupper(uniqid());
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}

