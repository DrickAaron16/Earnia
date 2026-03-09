<?php

namespace App\Services\Wallet;

use App\Models\Deposit;
use App\Models\GameSessionPlayer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    public function confirmDeposit(Deposit $deposit, float $fee = 0): WalletTransaction
    {
        if ($deposit->status !== 'pending') {
            throw new RuntimeException('Deposit already processed.');
        }

        return DB::transaction(function () use ($deposit, $fee) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($deposit->wallet_id)->lockForUpdate()->firstOrFail();

            $netAmount = max($deposit->amount - $fee, 0);

            $balanceBefore = $wallet->available_balance;
            $wallet->increment('available_balance', $netAmount);
            $wallet->refresh();
            $balanceAfter = $wallet->available_balance;

            $deposit->update([
                'fee' => $fee,
                'status' => 'succeeded',
                'processed_at' => now(),
            ]);

            return $this->recordTransaction(
                wallet: $wallet,
                type: 'deposit',
                direction: 'credit',
                amount: $deposit->amount,
                fee: $fee,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                transactable: $deposit,
            );
        });
    }

    public function failDeposit(Deposit $deposit, ?string $reason = null): Deposit
    {
        if ($deposit->status !== 'pending') {
            throw new RuntimeException('Deposit already processed.');
        }

        $deposit->update([
            'status' => 'failed',
            'metadata' => array_merge($deposit->metadata ?? [], [
                'failure_reason' => $reason,
            ]),
            'processed_at' => now(),
        ]);

        return $deposit;
    }

    public function approveWithdrawal(Withdrawal $withdrawal, float $fee = 0): WalletTransaction
    {
        if (! in_array($withdrawal->status, ['pending', 'under_review', 'processing'], true)) {
            throw new RuntimeException('Withdrawal not in approvable state.');
        }

        return DB::transaction(function () use ($withdrawal, $fee) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            if ($wallet->locked_balance < $withdrawal->amount) {
                throw new RuntimeException('Locked balance insufficient.');
            }

            $wallet->decrement('locked_balance', $withdrawal->amount);

            $withdrawal->update([
                'fee' => $fee,
                'status' => 'succeeded',
                'processed_at' => now(),
            ]);

            return $this->recordTransaction(
                wallet: $wallet,
                type: 'withdrawal',
                direction: 'debit',
                amount: $withdrawal->amount,
                fee: $fee,
                balanceBefore: $wallet->available_balance,
                balanceAfter: $wallet->available_balance,
                transactable: $withdrawal,
            );
        });
    }

    public function rejectWithdrawal(Withdrawal $withdrawal, ?string $reason = null): Withdrawal
    {
        if (! in_array($withdrawal->status, ['pending', 'under_review'], true)) {
            throw new RuntimeException('Withdrawal not in rejectable state.');
        }

        return DB::transaction(function () use ($withdrawal, $reason) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->decrement('locked_balance', $withdrawal->amount);
            $wallet->increment('available_balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'failed',
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'rejection_reason' => $reason,
                ]),
                'processed_at' => now(),
            ]);

            return $withdrawal;
        });
    }

    public function debit(Wallet $wallet, float $amount, string $type, mixed $transactable = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $amount, $type, $transactable) {
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedWallet->available_balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $balanceBefore = $lockedWallet->available_balance;
            $lockedWallet->decrement('available_balance', $amount);
            $lockedWallet->refresh();

            return $this->recordTransaction(
                wallet: $lockedWallet,
                type: $type,
                direction: 'debit',
                amount: $amount,
                fee: 0,
                balanceBefore: $balanceBefore,
                balanceAfter: $lockedWallet->available_balance,
                transactable: $transactable,
            );
        });
    }

    public function credit(Wallet $wallet, float $amount, string $type, mixed $transactable = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $amount, $type, $transactable) {
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = $lockedWallet->available_balance;
            $lockedWallet->increment('available_balance', $amount);
            $lockedWallet->refresh();

            return $this->recordTransaction(
                wallet: $lockedWallet,
                type: $type,
                direction: 'credit',
                amount: $amount,
                fee: 0,
                balanceBefore: $balanceBefore,
                balanceAfter: $lockedWallet->available_balance,
                transactable: $transactable,
            );
        });
    }

    public function reserveForGame(GameSessionPlayer $player): WalletTransaction
    {
        $amount = $player->bet_amount;

        if ($amount <= 0) {
            throw new RuntimeException('Bet amount must be positive.');
        }

        return DB::transaction(function () use ($player, $amount) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('user_id', $player->user_id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $balanceBefore = $wallet->available_balance;
            $wallet->decrement('available_balance', $amount);
            $wallet->increment('locked_balance', $amount);
            $wallet->refresh();

            $transaction = $this->recordTransaction(
                wallet: $wallet,
                type: 'bet',
                direction: 'debit',
                amount: $amount,
                fee: 0,
                balanceBefore: $balanceBefore,
                balanceAfter: $wallet->available_balance,
                transactable: $player,
            );

            $player->update([
                'wallet_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function releaseGameStake(GameSessionPlayer $player, float $payoutAmount): ?WalletTransaction
    {
        return DB::transaction(function () use ($player, $payoutAmount) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('user_id', $player->user_id)->lockForUpdate()->firstOrFail();

            if ($wallet->locked_balance < $player->bet_amount) {
                throw new RuntimeException('Locked balance insufficient for release.');
            }

            $wallet->decrement('locked_balance', $player->bet_amount);

            if ($payoutAmount <= 0) {
                return null;
            }

            $balanceBefore = $wallet->available_balance;
            $wallet->increment('available_balance', $payoutAmount);
            $wallet->refresh();

            return $this->recordTransaction(
                wallet: $wallet,
                type: 'win',
                direction: 'credit',
                amount: $payoutAmount,
                fee: 0,
                balanceBefore: $balanceBefore,
                balanceAfter: $wallet->available_balance,
                transactable: $player,
            );
        });
    }

    protected function recordTransaction(
        Wallet $wallet,
        string $type,
        string $direction,
        float $amount,
        float $fee,
        float $balanceBefore,
        float $balanceAfter,
        mixed $transactable = null,
    ): WalletTransaction {
        $transaction = new WalletTransaction([
            'reference' => Str::orderedUuid()->toString(),
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'fee' => $fee,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'status' => 'succeeded',
            'processed_at' => now(),
        ]);

        $transaction->wallet()->associate($wallet);

        if ($transactable) {
            $transaction->transactable()->associate($transactable);
        }

        $transaction->save();

        return $transaction;
    }
}

