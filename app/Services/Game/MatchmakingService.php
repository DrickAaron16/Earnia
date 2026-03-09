<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\MatchmakingTicket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchmakingService
{
    public function __construct(
        protected GameSessionService $sessionService
    ) {
    }

    public function createTicket(User $user, Game $game, array $data): array
    {
        return DB::transaction(function () use ($user, $game, $data) {
            $existing = MatchmakingTicket::query()
                ->where('user_id', $user->id)
                ->where('status', 'waiting')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw new RuntimeException('You already have an active matchmaking ticket.');
            }

            $maxPlayers = min($data['max_players'] ?? $game->max_players, $game->max_players);
            $ticket = MatchmakingTicket::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'mode' => $data['mode'],
                'stake_amount' => $data['stake_amount'],
                'max_players' => $maxPlayers,
                'status' => 'waiting',
                'skill_rating' => $data['skill_rating'] ?? null,
                'filters' => $data['filters'] ?? null,
                'expires_at' => now()->addMinutes(5),
            ]);

            $session = null;

            if ($ticket->mode === 'solo') {
                $session = $this->sessionService->createFromTickets($game, collect([$ticket]), $ticket->mode, $ticket->stake_amount, 1);
            } else {
                $required = $ticket->mode === 'duel' ? 2 : $maxPlayers;

                $candidates = MatchmakingTicket::query()
                    ->where('game_id', $game->id)
                    ->where('mode', $ticket->mode)
                    ->where('stake_amount', $ticket->stake_amount)
                    ->where('status', 'waiting')
                    ->where('id', '!=', $ticket->id)
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->take($required - 1)
                    ->get();

                if ($candidates->count() >= ($required - 1)) {
                    $tickets = $candidates->push($ticket);
                    $session = $this->sessionService->createFromTickets($game, $tickets, $ticket->mode, $ticket->stake_amount, $maxPlayers);
                }
            }

            return [
                'ticket' => $ticket->fresh(),
                'session' => $session,
            ];
        });
    }

    public function cancelTicket(MatchmakingTicket $ticket): MatchmakingTicket
    {
        if ($ticket->status !== 'waiting') {
            throw new RuntimeException('Ticket cannot be cancelled.');
        }

        $ticket->update([
            'status' => 'cancelled',
        ]);

        return $ticket;
    }

    public function listTickets(User $user): LengthAwarePaginator
    {
        return MatchmakingTicket::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);
    }
}

