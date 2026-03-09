<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMockController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function __invoke(string $provider, Request $request): JsonResponse
    {
        $this->authorizeRequest($request);

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'status' => ['required', 'in:success,failed'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $deposit = Deposit::query()
            ->where('external_reference', $data['reference'])
            ->where('provider', $provider)
            ->firstOrFail();

        if ($data['status'] === 'success') {
            $transaction = $this->walletService->confirmDeposit($deposit, $data['fee'] ?? 0);

            return response()->json([
                'status' => 'confirmed',
                'transaction' => $transaction,
            ]);
        }

        $this->walletService->failDeposit($deposit, $data['reason'] ?? null);

        return response()->json([
            'status' => 'failed',
        ]);
    }

    protected function authorizeRequest(Request $request): void
    {
        $secret = config('services.mock_payments.secret');

        if ($secret && $request->header('X-Mock-Secret') !== $secret) {
            abort(403, 'Invalid mock secret.');
        }
    }
}

