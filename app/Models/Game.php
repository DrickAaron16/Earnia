<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'min_players',
        'max_players',
        'default_mode',
        'min_stake',
        'max_stake',
        'is_active',
        'requires_rng',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_rng' => 'boolean',
        'min_stake' => 'decimal:2',
        'max_stake' => 'decimal:2',
        'settings' => 'array',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MatchmakingTicket::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
