<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'game_session_id',
        'mode',
        'stake_amount',
        'max_players',
        'status',
        'skill_rating',
        'filters',
        'expires_at',
    ];

    protected $casts = [
        'stake_amount' => 'decimal:2',
        'filters' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
