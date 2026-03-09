<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\DepositRequest;
use App\Http\Requests\Wallet\WithdrawalRequest;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Payment\PaygateService;

class WalletController extends Controller
{
    public function __construct(
        protected PaygateService $paygateService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/wallet",
     *     summary="Get user wallet information",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallet information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Wallet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $this->resolveWallet($user);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $wallet->id,
                'balance' => $wallet->available_balance,
                'available_balance' => $wallet->available_balance,
                'locked_balance' => $wallet->locked_balance,
                'currency' => $wallet->currency,
                'is_active' => $wallet->status === 'active',
                'status' => $wallet->status,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/wallet/transactions",
     *     summary="Get wallet transaction history",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WalletTransaction")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=67)
     *             )
     *         )
     *     )
     * )
     */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());

        $transactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/wallet/deposits",
     *     summary="Get deposit history",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Deposit history retrieved successfully"
     *     )
     * )
     */
    public function deposits(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());

        $deposits = $wallet->deposits()
            ->latest()
            ->paginate(15);

        return response()->json($deposits);
    }

    /**
     * @OA\Get(
     *     path="/wallet/withdrawals",
     *     summary="Get withdrawal history",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal history retrieved successfully"
     *     )
     * )
     */
    public function withdrawals(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());

        $withdrawals = $wallet->withdrawals()
            ->latest()
            ->paginate(15);

        return response()->json($withdrawals);
    }

    /**
     * @OA\Post(
     *     path="/wallet/deposits",
     *     summary="Request a deposit",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"provider","amount"},
     *             @OA\Property(property="provider", type="string", example="paygate", description="Payment provider"),
     *             @OA\Property(property="payment_method", type="string", example="card", description="Payment method"),
     *             @OA\Property(property="amount", type="number", format="float", example=5000.00, description="Deposit amount"),
     *             @OA\Property(property="return_url", type="string", example="https://app.earnia.com/wallet", description="Return URL after payment"),
     *             @OA\Property(property="note", type="string", example="Monthly deposit", description="Optional note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Deposit request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="deposit", type="object"),
     *             @OA\Property(property="payment", type="object"),
     *             @OA\Property(property="message", type="string", example="Deposit initialized. Awaiting provider confirmation.")
     *         )
     *     )
     * )
     */
    public function requestDeposit(DepositRequest $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());
        $data = $request->validated();

        $deposit = Deposit::create([
            'wallet_id' => $wallet->id,
            'provider' => $data['provider'],
            'payment_method' => $data['payment_method'] ?? null,
            'amount' => $data['amount'],
            'status' => 'pending',
            'metadata' => [
                'note' => $data['note'] ?? null,
            ],
            'external_reference' => Str::uuid()->toString(),
        ]);

        $payment = null;

        if ($deposit->provider === 'paygate') {
            $returnUrl = $data['return_url'] ?? config('services.paygate.return_url') ?? url('/');
            $payment = $this->paygateService->initiateDeposit(
                deposit: $deposit,
                returnUrl: $returnUrl,
                notifyUrl: route('payments.paygate.callback'),
                customerEmail: $request->user()->email,
                customerPhone: $request->user()->phone ?? null,
            );

            $deposit->update([
                'metadata' => array_merge($deposit->metadata ?? [], [
                    'paygate' => $payment,
                ]),
                'external_reference' => $payment['raw']['REFERENCE'] ?? $deposit->external_reference,
            ]);
        }

        return response()->json([
            'deposit' => $deposit,
            'payment' => $payment,
            'message' => 'Deposit initialized. Awaiting provider confirmation.',
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/wallet/withdrawals",
     *     summary="Request a withdrawal",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount","destination"},
     *             @OA\Property(property="amount", type="number", format="float", example=2000.00, description="Withdrawal amount"),
     *             @OA\Property(property="destination", type="string", example="bank_account", description="Withdrawal destination"),
     *             @OA\Property(property="provider", type="string", example="bank_transfer", description="Withdrawal provider"),
     *             @OA\Property(property="note", type="string", example="Monthly withdrawal", description="Optional note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Withdrawal request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="withdrawal", type="object"),
     *             @OA\Property(property="message", type="string", example="Withdrawal request submitted for review.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Insufficient balance",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Insufficient balance.")
     *         )
     *     )
     * )
     */
    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());
        $data = $request->validated();

        $withdrawal = DB::transaction(function () use ($wallet, $data) {
            $lockedWallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWallet->available_balance < $data['amount']) {
                abort(422, 'Insufficient balance.');
            }

            $lockedWallet->decrement('available_balance', $data['amount']);
            $lockedWallet->increment('locked_balance', $data['amount']);

            return Withdrawal::create([
                'wallet_id' => $lockedWallet->id,
                'amount' => $data['amount'],
                'destination' => $data['destination'],
                'provider' => $data['provider'] ?? null,
                'external_reference' => Str::uuid()->toString(),
                'status' => 'pending',
                'metadata' => [
                    'note' => $data['note'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'withdrawal' => $withdrawal,
            'message' => 'Withdrawal request submitted for review.',
        ], 201);
    }

    private function resolveWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'currency' => 'XAF',
                'available_balance' => 0.00,
                'locked_balance' => 0.00,
                'status' => 'active'
            ]
        );
    }
}

