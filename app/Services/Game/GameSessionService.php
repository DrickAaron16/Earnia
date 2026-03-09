<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\GameSessionPlayer;
use App\Models\MatchmakingTicket;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GameSessionService
{
    public function __construct(
        protected WalletService $walletService,
        protected RngService $rngService
    ) {
    }

    /**
     * @param  Collection<int, MatchmakingTicket>  $tickets
     */
    public function createFromTickets(Game $game, Collection $tickets, string $mode, float $stakeAmount, int $maxPlayers): GameSession
    {
        if ($tickets->isEmpty()) {
            throw new RuntimeException('Tickets collection cannot be empty.');
        }

        return DB::transaction(function () use ($game, $tickets, $mode, $stakeAmount, $maxPlayers) {
            $session = GameSession::create([
                'game_id' => $game->id,
                'host_user_id' => $tickets->first()->user_id,
                'mode' => $mode,
                'stake_amount' => $stakeAmount,
                'pot_amount' => $stakeAmount * $tickets->count(),
                'status' => 'in_progress',
                'max_players' => $maxPlayers,
                'started_at' => now(),
                'metadata' => [
                    'ticket_ids' => $tickets->pluck('id')->all(),
                ],
            ]);

            $players = $tickets->map(function (MatchmakingTicket $ticket) use ($session, $stakeAmount) {
                return GameSessionPlayer::create([
                    'game_session_id' => $session->id,
                    'user_id' => $ticket->user_id,
                    'bet_amount' => $stakeAmount,
                    'status' => 'playing',
                ]);
            });

            $players->each(fn (GameSessionPlayer $player) => $this->walletService->reserveForGame($player));

            MatchmakingTicket::query()
                ->whereIn('id', $tickets->pluck('id'))
                ->update([
                    'status' => 'matched',
                    'game_session_id' => $session->id,
                ]);

            $this->rngService->issueSeed($session, [
                'players' => $players->pluck('user_id')->all(),
            ]);

            return $session->load(['players.user', 'game']);
        });
    }

    public function submitScore(GameSession $session, User $user, int $score): GameSession
    {
        if ($session->status !== 'in_progress') {
            throw new RuntimeException('Session is not accepting scores.');
        }

        /** @var GameSessionPlayer|null $player */
        $player = $session->players()->where('user_id', $user->id)->first();

        if (! $player) {
            throw new RuntimeException('Player not part of this session.');
        }

        if (in_array($player->status, ['completed', 'forfeited'], true)) {
            throw new RuntimeException('Score already submitted.');
        }

        $player->update([
            'score' => $score,
            'status' => 'completed',
        ]);

        $session->refresh();

        if ($session->players()->whereNotIn('status', ['completed', 'forfeited'])->exists() === false) {
            $session = $this->complete($session);
        }

        return $session->load('players');
    }

    public function complete(GameSession $session): GameSession
    {
        if ($session->status === 'completed') {
            return $session;
        }

        return DB::transaction(function () use ($session) {
            $players = $session->players()->orderByDesc('score')->get();

            if ($players->isEmpty()) {
                throw new RuntimeException('Session has no players to complete.');
            }

            $winner = $players->first();
            $pot = $session->stake_amount * $players->count();

            $placements = 1;

            foreach ($players as $player) {
                $isWinner = $player->id === $winner->id;
                $payout = $isWinner ? $pot : 0;

                $player->update([
                    'placement' => $placements++,
                    'payout_amount' => $payout,
                    'is_winner' => $isWinner,
                ]);

                $this->walletService->releaseGameStake($player, $payout);
            }

            $session->forceFill([
                'status' => 'completed',
                'pot_amount' => $pot,
                'ended_at' => now(),
            ])->save();

            GameResult::updateOrCreate(
                ['game_session_id' => $session->id],
                [
                    'results' => $players->map(fn (GameSessionPlayer $player) => [
                        'user_id' => $player->user_id,
                        'score' => $player->score,
                        'placement' => $player->placement,
                        'payout' => $player->payout_amount,
                        'is_winner' => $player->is_winner,
                    ])->all(),
                    'rng_seed' => $session->rng_seed,
                    'rng_hash' => $session->rng_seed ? hash('sha256', $session->rng_seed) : null,
                ]
            );

            return $session->load(['players.user', 'result']);
        });
    }
}

