<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;

class MatchmakingService
{
    public function findMatch(Game $game, User $user, float $betAmount, string $mode = null): ?GameSession
    {
        $mode = $mode ?? $game->mode;

        $minBet = $betAmount * 0.8;
        $maxBet = $betAmount * 1.2;

        $session = GameSession::where('game_id', $game->id)
            ->where('status', 'waiting')
            ->where('mode', $mode)
            ->where('current_players', '<', $game->max_players)
            ->whereHas('bets', function ($query) use ($minBet, $maxBet) {
                $query->whereBetween('amount', [$minBet, $maxBet]);
            })
            ->first();

        return $session;
    }

    public function createSession(Game $game, float $betAmount, string $mode = null): GameSession
    {
        $mode = $mode ?? $game->mode;

        return GameSession::create([
            'game_id' => $game->id,
            'session_code' => GameSession::generateSessionCode(),
            'status' => 'waiting',
            'mode' => $mode,
            'total_pot' => $betAmount,
            'platform_fee' => $betAmount * 0.05,
            'current_players' => 0,
            'max_players' => $game->max_players,
        ]);
    }

    public function isMatchmakingTimeout(GameSession $session, int $timeoutSeconds = 60): bool
    {
        $createdAt = $session->created_at;
        $now = now();
        
        return $now->diffInSeconds($createdAt) >= $timeoutSeconds;
    }
}

