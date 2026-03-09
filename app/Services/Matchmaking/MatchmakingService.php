<?php

namespace App\Services\Matchmaking;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\MatchmakingTicket;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchmakingService
{
    public function __construct(
        protected GameSessionService $gameSessionService,
        protected WalletService $walletService,
    ) {
    }

    public function createTicket(User $user, Game $game, array $payload): MatchmakingTicket
    {
        return DB::transaction(function () use ($user, $game, $payload) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'currency' => 'USD',
            ]);

            $this->walletService->reserveStake($wallet, $payload['stake_amount'], useTransaction: false);

            $ticket = MatchmakingTicket::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'mode' => $payload['mode'],
                'stake_amount' => $payload['stake_amount'],
                'max_players' => $payload['max_players'],
                'skill_rating' => $payload['skill_rating'] ?? null,
                'filters' => $payload['filters'] ?? null,
                'expires_at' => now()->addMinutes(5),
            ]);

            return $ticket;
        });
    }

    public function cancelTicket(MatchmakingTicket $ticket): MatchmakingTicket
    {
        if ($ticket->status !== 'waiting') {
            throw new RuntimeException('Ticket cannot be cancelled.');
        }

        return DB::transaction(function () use ($ticket) {
            $wallet = $ticket->user->wallet()->lockForUpdate()->firstOrFail();

            $this->walletService->releaseStake($wallet, $ticket->stake_amount, useTransaction: false);

            $ticket->update([
                'status' => 'cancelled',
                'expires_at' => now(),
            ]);

            return $ticket;
        });
    }

    public function attemptMatch(MatchmakingTicket $ticket): ?GameSession
    {
        return DB::transaction(function () use ($ticket) {
            /** @var MatchmakingTicket $freshTicket */
            $freshTicket = MatchmakingTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if ($freshTicket->status !== 'waiting') {
                return $freshTicket->session;
            }

            $needed = max($freshTicket->max_players - 1, 1);

            /** @var Collection<int, MatchmakingTicket> $opponents */
            $opponents = MatchmakingTicket::query()
                ->where('game_id', $freshTicket->game_id)
                ->where('mode', $freshTicket->mode)
                ->where('stake_amount', $freshTicket->stake_amount)
                ->where('status', 'waiting')
                ->where('id', '!=', $freshTicket->id)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->take($needed)
                ->get();

            if ($opponents->count() < $needed) {
                return null;
            }

            $tickets = $opponents->push($freshTicket)->all();

            return $this->gameSessionService->createSessionFromTickets($tickets);
        });
    }
}

