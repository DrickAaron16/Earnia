<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    public function processFlutterwaveDeposit(float $amount, string $email, string $phone, string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => 'XAF',
                'redirect_url' => config('app.url') . '/api/payments/flutterwave/callback',
                'customer' => [
                    'email' => $email,
                    'phonenumber' => $phone,
                ],
                'customizations' => [
                    'title' => 'Earnia Deposit',
                    'description' => 'Deposit to Earnia wallet',
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payment_reference' => $reference,
                    'payment_url' => $data['data']['link'],
                    'status' => 'pending',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to initialize Flutterwave payment',
                'status' => 'failed',
            ];
        } catch (Exception $e) {
            Log::error('Flutterwave payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment service unavailable',
                'status' => 'failed',
            ];
        }
    }

    public function processStripeDeposit(float $amount, string $email, string $token): array
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => 'xaf',
                'payment_method' => $token,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'receipt_email' => $email,
            ]);

            return [
                'success' => true,
                'payment_reference' => $paymentIntent->id,
                'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'pending',
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'status' => 'failed',
            ];
        }
    }

    public function processMobileMoneyDeposit(float $amount, string $phone, string $provider, string $reference): array
    {
        try {
            // Simulate mobile money API call
            $providers = ['mtn', 'orange', 'moov'];
            
            if (!in_array(strtolower($provider), $providers)) {
                return [
                    'success' => false,
                    'message' => 'Unsupported mobile money provider',
                    'status' => 'failed',
                ];
            }

            // In production, integrate with actual mobile money APIs
            // For now, simulate successful initiation
            return [
                'success' => true,
                'payment_reference' => $reference,
                'status' => 'pending',
                'ussd_code' => '*126*' . $amount . '#',
                'instructions' => "Dial {$provider} USSD code to complete payment",
            ];
        } catch (Exception $e) {
            Log::error('Mobile money payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Mobile money service unavailable',
                'status' => 'failed',
            ];
        }
    }

    public function processWithdrawal(Withdrawal $withdrawal): array
    {
        try {
            return DB::transaction(function () use ($withdrawal) {
                $user = $withdrawal->user;
                $wallet = $user->wallet;

                // Validate withdrawal
                if ($wallet->available_balance < $withdrawal->amount) {
                    throw new Exception('Insufficient balance');
                }

                // Process withdrawal based on method
                switch ($withdrawal->method) {
                    case 'bank_transfer':
                        return $this->processBankWithdrawal($withdrawal);
                    case 'mobile_money':
                        return $this->processMobileMoneyWithdrawal($withdrawal);
                    default:
                        throw new Exception('Unsupported withdrawal method');
                }
            });
        } catch (Exception $e) {
            Log::error('Withdrawal processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function processBankWithdrawal(Withdrawal $withdrawal): array
    {
        // Simulate bank transfer processing
        $withdrawal->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);

        // Create wallet transaction
        WalletTransaction::create([
            'wallet_id' => $withdrawal->user->wallet->id,
            'type' => 'withdrawal',
            'amount' => -$withdrawal->amount,
            'reference' => $withdrawal->reference,
            'status' => 'completed',
            'description' => 'Bank withdrawal',
        ]);

        // Update wallet balance
        $withdrawal->user->wallet->decrement('available_balance', $withdrawal->amount);

        return [
            'success' => true,
            'message' => 'Bank withdrawal initiated successfully',
            'processing_time' => '1-3 business days',
        ];
    }

    private function processMobileMoneyWithdrawal(Withdrawal $withdrawal): array
    {
        // Simulate mobile money withdrawal
        $withdrawal->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        // Create wallet transaction
        WalletTransaction::create([
            'wallet_id' => $withdrawal->user->wallet->id,
            'type' => 'withdrawal',
            'amount' => -$withdrawal->amount,
            'reference' => $withdrawal->reference,
            'status' => 'completed',
            'description' => 'Mobile money withdrawal',
        ]);

        // Update wallet balance
        $withdrawal->user->wallet->decrement('available_balance', $withdrawal->amount);

        return [
            'success' => true,
            'message' => 'Mobile money withdrawal completed successfully',
        ];
    }

    public function verifyPayment(string $reference, string $provider): array
    {
        try {
            switch (strtolower($provider)) {
                case 'flutterwave':
                    return $this->verifyFlutterwavePayment($reference);
                case 'stripe':
                    return $this->verifyStripePayment($reference);
                case 'mobile_money':
                    return $this->verifyMobileMoneyPayment($reference);
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported payment provider',
                    ];
            }
        } catch (Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];
        }
    }

    private function verifyFlutterwavePayment(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
        ])->get("https://api.flutterwave.com/v3/transactions/{$reference}/verify");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status' => $data['data']['status'] === 'successful' ? 'completed' : 'failed',
                'verified' => true,
                'amount' => $data['data']['amount'],
                'currency' => $data['data']['currency'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to verify Flutterwave payment',
        ];
    }

    private function verifyStripePayment(string $reference): array
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        $paymentIntent = $stripe->paymentIntents->retrieve($reference);
        
        return [
            'success' => true,
            'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'failed',
            'verified' => true,
            'amount' => $paymentIntent->amount / 100,
            'currency' => strtoupper($paymentIntent->currency),
        ];
    }

    private function verifyMobileMoneyPayment(string $reference): array
    {
        // Simulate mobile money verification
        // In production, integrate with actual mobile money APIs
        return [
            'success' => true,
            'status' => 'completed',
            'verified' => true,
        ];
    }

    public function processDeposit(User $user, float $amount, string $provider, array $paymentData): array
    {
        try {
            return DB::transaction(function () use ($user, $amount, $provider, $paymentData) {
                // Create deposit record
                $deposit = Deposit::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $paymentData['currency'] ?? 'XAF',
                    'provider' => $provider,
                    'reference' => $paymentData['reference'],
                    'status' => 'pending',
                    'payment_data' => $paymentData,
                ]);

                // Process payment based on provider
                $result = match ($provider) {
                    'flutterwave' => $this->processFlutterwaveDeposit(
                        $amount,
                        $user->email,
                        $user->phone ?? $user->phone_number,
                        $paymentData['reference']
                    ),
                    'stripe' => $this->processStripeDeposit(
                        $amount,
                        $user->email,
                        $paymentData['token']
                    ),
                    'mobile_money' => $this->processMobileMoneyDeposit(
                        $amount,
                        $user->phone ?? $user->phone_number,
                        $paymentData['provider'],
                        $paymentData['reference']
                    ),
                    default => throw new Exception('Unsupported payment provider')
                };

                // Update deposit status
                $deposit->update(['status' => $result['status']]);

                return array_merge($result, ['deposit_id' => $deposit->id]);
            });
        } catch (Exception $e) {
            Log::error('Deposit processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

