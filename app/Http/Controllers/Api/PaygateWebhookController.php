<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Payment\PaygateService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaygateWebhookController extends Controller
{
    public function __construct(
        protected PaygateService $paygateService,
        protected WalletService $walletService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $verification = $this->paygateService->verifyCallback($request->all());

        if (! $verification['valid']) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Invalid checksum',
            ], 400);
        }

        $deposit = Deposit::query()
            ->where('provider', 'paygate')
            ->where('external_reference', $verification['reference'])
            ->firstOrFail();

        if ($verification['approved']) {
            $transaction = $this->walletService->confirmDeposit($deposit);

            return response()->json([
                'status' => 'succeeded',
                'deposit' => $deposit->fresh(),
                'transaction' => $transaction,
            ]);
        }

        $reason = $request->input('RESULT_DESC') ?? 'Payment not approved';
        $deposit = $this->walletService->failDeposit($deposit, $reason);

        return response()->json([
            'status' => 'failed',
            'deposit' => $deposit,
            'reason' => $reason,
        ], 400);
    }
}


