<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'game_id',
        'entry_fee',
        'total_prize',
        'max_participants',
        'current_participants',
        'start_date',
        'end_date',
        'status',
        'prize_distribution',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'total_prize' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'prize_distribution' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function isFull(): bool
    {
        if (!$this->max_participants) {
            return false;
        }

        return $this->current_participants >= $this->max_participants;
    }

    public function isOpenForRegistration(): bool
    {
        return $this->is_active 
            && $this->status === 'open' 
            && !$this->isFull()
            && $this->start_date->isFuture();
    }
}

