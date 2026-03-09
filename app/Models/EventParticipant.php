<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'entry_fee_paid',
        'status',
        'final_position',
        'prize_won',
        'performance_data',
        'registered_at',
    ];

    protected $casts = [
        'entry_fee_paid' => 'decimal:2',
        'prize_won' => 'decimal:2',
        'performance_data' => 'array',
        'registered_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

