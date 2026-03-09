<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency' => 'XAF',
            'available_balance' => $this->faker->randomFloat(2, 0, 10000),
            'locked_balance' => $this->faker->randomFloat(2, 0, 1000),
            'status' => 'active',
            'limits' => [
                'daily_deposit' => 100000,
                'daily_withdrawal' => 50000,
                'monthly_deposit' => 1000000,
                'monthly_withdrawal' => 500000,
            ],
        ];
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the wallet has a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'available_balance' => $balance,
            'locked_balance' => 0.00,
        ]);
    }
}