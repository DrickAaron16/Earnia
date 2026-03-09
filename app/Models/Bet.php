<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_session_id',
        'game_id',
        'amount',
        'status',
        'potential_win',
        'actual_win',
        'position',
        'game_result_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'potential_win' => 'decimal:2',
        'actual_win' => 'decimal:2',
        'game_result_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function markAsWon(float $winAmount, int $position = null): bool
    {
        $this->status = 'won';
        $this->actual_win = $winAmount;
        $this->position = $position;
        return $this->save();
    }

    public function markAsLost(): bool
    {
        $this->status = 'lost';
        return $this->save();
    }
}

