<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'available_balance' => 1000.00,
            'locked_balance' => 0.00,
            'currency' => 'XAF',
        ]);
    }

    public function test_user_can_view_wallet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
                        ->getJson('/api/wallet');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'balance',
                        'available_balance',
                        'locked_balance',
                        'currency',
                        'is_active',
                        'status'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'balance' => 1000.00,
                        'available_balance' => 1000.00,
                        'locked_balance' => 0.00,
                        'currency' => 'XAF',
                        'is_active' => true,
                    ]
                ]);
    }

    public function test_user_can_view_wallet_transactions(): void
    {
        // Create some transactions
        WalletTransaction::factory()->count(3)->create([
            'wallet_id' => $this->wallet->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
                        ->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'amount',
                            'status',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]);
    }

    public function test_wallet_balance_calculations(): void
    {
        $this->assertEquals(1000.00, $this->wallet->balance);
        $this->assertEquals(1000.00, $this->wallet->total_balance);
        $this->assertTrue($this->wallet->is_active);

        // Update balances
        $this->wallet->update([
            'available_balance' => 800.00,
            'locked_balance' => 200.00,
        ]);

        $this->wallet->refresh();
        $this->assertEquals(800.00, $this->wallet->balance);
        $this->assertEquals(1000.00, $this->wallet->total_balance);
    }

    public function test_wallet_is_created_for_new_user(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);
        
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals('XAF', $user->wallet->currency);
        $this->assertEquals(0.00, $user->wallet->available_balance);
    }

    public function test_unauthenticated_user_cannot_access_wallet(): void
    {
        $response = $this->getJson('/api/wallet');
        $response->assertStatus(401);

        $response = $this->getJson('/api/wallet/transactions');
        $response->assertStatus(401);
    }
}