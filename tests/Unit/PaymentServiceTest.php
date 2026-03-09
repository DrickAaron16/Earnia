<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Withdrawal;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    public function test_flutterwave_deposit_initialization(): void
    {
        $result = $this->paymentService->processFlutterwaveDeposit(
            amount: 1000.00,
            email: 'test@example.com',
            phone: '+237123456789',
            reference: 'TEST_REF_123'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('payment_reference', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function test_stripe_deposit_initialization(): void
    {
        $result = $this->paymentService->processStripeDeposit(
            amount: 1000.00,
            email: 'test@example.com',
            token: 'tok_test_123'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('payment_reference', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function test_mobile_money_deposit_initialization(): void
    {
        $result = $this->paymentService->processMobileMoneyDeposit(
            amount: 1000.00,
            phone: '+237123456789',
            provider: 'mtn',
            reference: 'MM_REF_123'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('payment_reference', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function test_mobile_money_unsupported_provider(): void
    {
        $result = $this->paymentService->processMobileMoneyDeposit(
            amount: 1000.00,
            phone: '+237123456789',
            provider: 'unsupported',
            reference: 'MM_REF_123'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Unsupported mobile money provider', $result['message']);
    }

    public function test_payment_verification(): void
    {
        $result = $this->paymentService->verifyPayment(
            reference: 'TEST_REF_123',
            provider: 'flutterwave'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_process_deposit_with_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+237123456789',
        ]);

        $result = $this->paymentService->processDeposit(
            user: $user,
            amount: 1000.00,
            provider: 'mobile_money',
            paymentData: [
                'reference' => 'TEST_REF_123',
                'provider' => 'mtn',
                'currency' => 'XAF',
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('deposit_id', $result);
    }
}