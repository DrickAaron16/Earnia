<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function deposits(Request $request): JsonResponse
    {
        $deposits = Deposit::query()
            ->with('wallet.user')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return response()->json($deposits);
    }

    public function confirmDeposit(Request $request, Deposit $deposit): JsonResponse
    {
        $data = $request->validate([
            'fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $transaction = $this->walletService->confirmDeposit($deposit, $data['fee'] ?? 0);

        return response()->json([
            'deposit' => $deposit->fresh(),
            'transaction' => $transaction,
        ]);
    }

    public function failDeposit(Request $request, Deposit $deposit): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $deposit = $this->walletService->failDeposit($deposit, $data['reason'] ?? null);

        return response()->json([
            'deposit' => $deposit,
        ]);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::query()
            ->with('wallet.user')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $data = $request->validate([
            'fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $transaction = $this->walletService->approveWithdrawal($withdrawal, $data['fee'] ?? 0);
        $withdrawal->forceFill([
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return response()->json([
            'withdrawal' => $withdrawal->fresh(),
            'transaction' => $transaction,
        ]);
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $withdrawal = $this->walletService->rejectWithdrawal($withdrawal, $data['reason'] ?? null);
        $withdrawal->forceFill([
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return response()->json([
            'withdrawal' => $withdrawal,
        ]);
    }
}

