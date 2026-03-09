<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentEntryService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function register(Tournament $tournament, User $user): TournamentEntry
    {
        if (! in_array($tournament->status, ['upcoming', 'running'], true)) {
            throw new RuntimeException('Tournament not open for registration.');
        }

        if ($tournament->registration_starts_at && now()->lt($tournament->registration_starts_at)) {
            throw new RuntimeException('Registration not yet open.');
        }

        if ($tournament->registration_ends_at && now()->gt($tournament->registration_ends_at)) {
            throw new RuntimeException('Registration closed.');
        }

        if ($tournament->max_players && $tournament->entries()->whereNotIn('status', ['refunded'])->count() >= $tournament->max_players) {
            throw new RuntimeException('Tournament full.');
        }

        if (
            $tournament->entries()
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['refunded'])
                ->exists()
        ) {
            throw new RuntimeException('Already registered to this tournament.');
        }

        return DB::transaction(function () use ($tournament, $user) {
            if ($tournament->max_players) {
                $activeCount = TournamentEntry::query()
                    ->where('tournament_id', $tournament->id)
                    ->whereNotIn('status', ['refunded'])
                    ->lockForUpdate()
                    ->count();

                if ($activeCount >= $tournament->max_players) {
                    throw new RuntimeException('Tournament full.');
                }
            }

            $entry = TournamentEntry::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            if ($tournament->entry_fee > 0) {
                $wallet = $user->wallet()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['currency' => 'USD']
                );

                $transaction = $this->walletService->debit(
                    wallet: $wallet,
                    amount: $tournament->entry_fee,
                    type: 'tournament_entry',
                    transactable: $entry
                );

                $entry->update([
                    'wallet_transaction_id' => $transaction->id,
                ]);
            }

            return $entry->load('user');
        });
    }

    public function withdraw(TournamentEntry $entry): TournamentEntry
    {
        if (! in_array($entry->status, ['active', 'pending'], true)) {
            throw new RuntimeException('Entry cannot be withdrawn.');
        }

        return DB::transaction(function () use ($entry) {
            $entry->loadMissing('tournament', 'user');

            $entry->update([
                'status' => 'refund_pending',
            ]);

            if ($entry->tournament->entry_fee > 0 && $entry->wallet_transaction_id) {
                $wallet = $entry->user->wallet()->firstOrCreate(
                    ['user_id' => $entry->user_id],
                    ['currency' => 'USD']
                );

                $this->walletService->credit(
                    wallet: $wallet,
                    amount: $entry->tournament->entry_fee,
                    type: 'tournament_refund',
                    transactable: $entry
                );

                $entry->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            } else {
                $entry->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            }

            return $entry;
        });
    }
}

