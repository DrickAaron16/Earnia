<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['deposit', 'withdrawal', 'bet', 'win', 'refund'];
        $type = $this->faker->randomElement($types);
        
        return [
            'wallet_id' => Wallet::factory(),
            'type' => $type,
            'amount' => $type === 'withdrawal' ? -$this->faker->randomFloat(2, 10, 1000) : $this->faker->randomFloat(2, 10, 1000),
            'reference' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'description' => $this->faker->sentence(),
            'metadata' => [
                'source' => $this->faker->randomElement(['flutterwave', 'stripe', 'mobile_money']),
                'ip_address' => $this->faker->ipv4(),
            ],
        ];
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the transaction is a deposit.
     */
    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
            'amount' => $this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
            'amount' => -$this->faker->randomFloat(2, 10, 1000),
        ]);
    }
}