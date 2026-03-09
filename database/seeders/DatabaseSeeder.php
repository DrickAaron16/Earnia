<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Demo User',
            'username' => 'demo',
            'email' => 'test@example.com',
            'phone_number' => fake()->e164PhoneNumber(),
            'country_code' => 'US',
            'is_admin' => true,
        ]);

        Game::upsert([
            [
                'name' => 'Lightning Tap',
                'slug' => 'lightning-tap',
                'description' => 'Jeu de rapidité 1v1',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'duel',
                'min_stake' => 1,
                'max_stake' => 20,
                'is_active' => true,
                'requires_rng' => false,
            ],
            [
                'name' => 'Quiz Master',
                'slug' => 'quiz-master',
                'description' => 'Quiz multijoueur rapide',
                'min_players' => 1,
                'max_players' => 10,
                'default_mode' => 'multiplayer',
                'min_stake' => 0.5,
                'max_stake' => 50,
                'is_active' => true,
                'requires_rng' => false,
            ],
            [
                'name' => 'Wheel Fortune',
                'slug' => 'wheel-fortune',
                'description' => 'Roue de hasard avec RNG auditable.',
                'min_players' => 1,
                'max_players' => 4,
                'default_mode' => 'solo',
                'min_stake' => 0.2,
                'max_stake' => 25,
                'is_active' => true,
                'requires_rng' => true,
            ],
        ], ['slug'], [
            'name',
            'description',
            'min_players',
            'max_players',
            'default_mode',
            'min_stake',
            'max_stake',
            'is_active',
            'requires_rng',
        ]);

        $lightningTap = Game::where('slug', 'lightning-tap')->first();

        if ($lightningTap) {
            Tournament::firstOrCreate(
                ['slug' => 'daily-lightning-duel'],
                [
                    'game_id' => $lightningTap->id,
                    'title' => 'Daily Lightning Duel',
                    'slug' => 'daily-lightning-duel',
                    'description' => 'Tournoi rapide quotidien.',
                    'entry_fee' => 2,
                    'prize_pool' => 100,
                    'max_players' => 32,
                    'status' => 'upcoming',
                    'registration_starts_at' => now()->subDay(),
                    'registration_ends_at' => now()->addDay(),
                    'starts_at' => now()->addDay(),
                    'rules' => [
                        'rounds' => 5,
                        'score_type' => 'reaction',
                    ],
                    'payout_structure' => [
                        ['placement' => 1, 'percentage' => 0.7],
                        ['placement' => 2, 'percentage' => 0.2],
                        ['placement' => 3, 'percentage' => 0.1],
                    ],
                ]
            );
        }
    }
}
