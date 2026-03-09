<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'game_session_id',
        'wallet_transaction_id',
        'status',
        'seed',
        'placement',
        'payout_amount',
        'registered_at',
        'refunded_at',
    ];

    protected $casts = [
        'payout_amount' => 'decimal:2',
        'registered_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }
}

