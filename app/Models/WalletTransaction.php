<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Schema(
 *     schema="WalletTransaction",
 *     type="object",
 *     title="Wallet Transaction",
 *     description="Wallet transaction model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="wallet_id", type="integer", example=1),
 *     @OA\Property(property="reference", type="string", example="TXN-123456789"),
 *     @OA\Property(property="type", type="string", example="deposit", description="Transaction type: deposit, withdrawal, bet, win, etc."),
 *     @OA\Property(property="direction", type="string", example="credit", description="credit or debit"),
 *     @OA\Property(property="amount", type="number", format="float", example=1500.50),
 *     @OA\Property(property="fee", type="number", format="float", example=15.00),
 *     @OA\Property(property="balance_before", type="number", format="float", example=1000.00),
 *     @OA\Property(property="balance_after", type="number", format="float", example=2500.50),
 *     @OA\Property(property="status", type="string", example="completed"),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="processed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'reference',
        'type',
        'direction',
        'amount',
        'fee',
        'balance_before',
        'balance_after',
        'status',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }
}

