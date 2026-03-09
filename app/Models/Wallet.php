<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Wallet",
 *     type="object",
 *     title="Wallet",
 *     description="User wallet model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="currency", type="string", example="XAF"),
 *     @OA\Property(property="available_balance", type="number", format="float", example=1500.50),
 *     @OA\Property(property="locked_balance", type="number", format="float", example=100.00),
 *     @OA\Property(property="balance", type="number", format="float", example=1500.50, description="Alias for available_balance"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'available_balance',
        'locked_balance',
        'status',
        'limits',
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
        'limits' => 'array',
    ];

    protected $appends = ['balance', 'is_active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function getTotalBalanceAttribute(): float
    {
        return (float) ($this->available_balance + $this->locked_balance);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->available_balance;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}

