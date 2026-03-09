<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'host_user_id',
        'mode',
        'stake_amount',
        'pot_amount',
        'status',
        'max_players',
        'started_at',
        'ended_at',
        'rng_seed',
        'rng_signature',
        'metadata',
    ];

    protected $casts = [
        'stake_amount' => 'decimal:2',
        'pot_amount' => 'decimal:2',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameSessionPlayer::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(GameResult::class);
    }
}
