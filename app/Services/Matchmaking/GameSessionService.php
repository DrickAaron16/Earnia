<?php

namespace App\Services\Matchmaking;

use App\Models\GameSession;
use App\Models\GameSessionPlayer;
use App\Models\MatchmakingTicket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameSessionService
{
    /**
     * @param MatchmakingTicket[] $tickets
     */
    public function createSessionFromTickets(array $tickets): GameSession
    {
        return DB::transaction(function () use ($tickets) {
            /** @var Collection<int, MatchmakingTicket> $collection */
            $collection = collect($tickets);

            $game = $collection->first()->game;
            $mode = $collection->first()->mode;

            $stakePerPlayer = (float) $collection->first()->stake_amount;
            $playerCount = $collection->count();
            $pot = $stakePerPlayer * $playerCount;

            $session = GameSession::create([
                'game_id' => $game->id,
                'host_user_id' => $collection->first()->user_id,
                'mode' => $mode,
                'stake_amount' => $stakePerPlayer,
                'pot_amount' => $pot,
                'status' => 'matching',
                'max_players' => $playerCount,
                'metadata' => [
                    'ticket_ids' => $collection->pluck('id')->toArray(),
                ],
            ]);

            $collection->each(function (MatchmakingTicket $ticket) use ($session, $stakePerPlayer): void {
                GameSessionPlayer::create([
                    'game_session_id' => $session->id,
                    'user_id' => $ticket->user_id,
                    'bet_amount' => $stakePerPlayer,
                    'status' => 'pending',
                    'joined_at' => now(),
                ]);

                $ticket->update([
                    'status' => 'matched',
                    'game_session_id' => $session->id,
                ]);
            });

            return $session->load('players.user');
        });
    }
}

