<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentPayoutService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * @param  array<int, array{entry_id:int, placement:int, payout_amount:float}>  $results
     */
    public function settle(Tournament $tournament, array $results): Tournament
    {
        if ($tournament->status !== 'running') {
            throw new RuntimeException('Tournament not in running state.');
        }

        if (empty($results)) {
            throw new RuntimeException('Results payload is required.');
        }

        return DB::transaction(function () use ($tournament, $results) {
            $totalPayout = 0;

            foreach ($results as $result) {
                /** @var TournamentEntry $entry */
                $entry = $tournament->entries()->where('id', $result['entry_id'])->lockForUpdate()->firstOrFail();

                $payout = (float) $result['payout_amount'];
                $placement = (int) $result['placement'];

                $entry->update([
                    'placement' => $placement,
                    'payout_amount' => $payout,
                    'status' => $payout > 0 ? 'winner' : 'eliminated',
                ]);

                if ($payout > 0) {
                    $wallet = $entry->user->wallet()->firstOrCreate(
                        ['user_id' => $entry->user_id],
                        ['currency' => 'USD']
                    );

                    $this->walletService->credit(
                        wallet: $wallet,
                        amount: $payout,
                        type: 'tournament_payout',
                        transactable: $entry
                    );
                }

                $totalPayout += $payout;
            }

            if ($tournament->prize_pool && $totalPayout - $tournament->prize_pool > 0.01) {
                throw new RuntimeException('Total payout exceeds prize pool.');
            }

            $tournament->update([
                'status' => 'completed',
                'ends_at' => now(),
            ]);

            return $tournament->load('entries.user');
        });
    }
}

