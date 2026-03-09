<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            // Jeux de Logique
            [
                'name' => 'Sudoku Challenge',
                'slug' => 'sudoku-challenge',
                'description' => 'Résolvez des grilles de Sudoku 4x4 et 6x6 - Logique pure',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.5,
                'max_stake' => 20,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 300,
                    'type' => 'logic',
                    'difficulty_levels' => ['easy', 'medium', 'hard'],
                ],
            ],
            [
                'name' => 'Memory Master',
                'slug' => 'memory-master',
                'description' => 'Mémorisez et reproduisez des séquences - Entraînez votre mémoire',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.3,
                'max_stake' => 15,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 180,
                    'type' => 'memory',
                    'max_sequence_length' => 20,
                ],
            ],
            [
                'name' => 'Pattern Finder',
                'slug' => 'pattern-finder',
                'description' => 'Trouvez les motifs et séquences logiques - Raisonnement abstrait',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.5,
                'max_stake' => 25,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 240,
                    'type' => 'logic',
                    'rounds' => 10,
                ],
            ],
            [
                'name' => 'Math Sprint',
                'slug' => 'math-sprint',
                'description' => 'Calcul mental rapide - Addition, soustraction, multiplication',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'duel',
                'min_stake' => 0.2,
                'max_stake' => 30,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 120,
                    'type' => 'math',
                    'operations' => ['add', 'subtract', 'multiply'],
                ],
            ],
            
            // Jeux de Réflexion
            [
                'name' => 'Chess Puzzle',
                'slug' => 'chess-puzzle',
                'description' => 'Résolvez des problèmes d\'échecs - Mat en 2 ou 3 coups',
                'min_players' => 1,
                'max_players' => 1,
                'default_mode' => 'solo',
                'min_stake' => 1,
                'max_stake' => 50,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 300,
                    'type' => 'strategy',
                    'puzzle_types' => ['mate_in_2', 'mate_in_3', 'tactical'],
                ],
            ],
            [
                'name' => 'Word Chain',
                'slug' => 'word-chain',
                'description' => 'Créez des chaînes de mots - Vocabulaire et rapidité',
                'min_players' => 1,
                'max_players' => 4,
                'default_mode' => 'multiplayer',
                'min_stake' => 0.3,
                'max_stake' => 20,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 180,
                    'type' => 'vocabulary',
                    'min_word_length' => 3,
                ],
            ],
            [
                'name' => 'Logic Grid',
                'slug' => 'logic-grid',
                'description' => 'Résolvez des énigmes de grille logique - Déduction pure',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.5,
                'max_stake' => 25,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 360,
                    'type' => 'logic',
                    'grid_sizes' => ['3x3', '4x4'],
                ],
            ],
            [
                'name' => 'Color Code',
                'slug' => 'color-code',
                'description' => 'Déchiffrez le code de couleurs - Mastermind moderne',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.3,
                'max_stake' => 15,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 240,
                    'type' => 'logic',
                    'max_attempts' => 10,
                    'colors' => 6,
                ],
            ],
            
            // Jeux d'Intelligence
            [
                'name' => 'IQ Challenge',
                'slug' => 'iq-challenge',
                'description' => 'Tests de QI variés - Logique, spatial, numérique',
                'min_players' => 1,
                'max_players' => 10,
                'default_mode' => 'multiplayer',
                'min_stake' => 1,
                'max_stake' => 100,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 600,
                    'type' => 'intelligence',
                    'question_count' => 20,
                ],
            ],
            [
                'name' => 'Brain Teaser',
                'slug' => 'brain-teaser',
                'description' => 'Énigmes et casse-têtes - Pensée latérale',
                'min_players' => 1,
                'max_players' => 4,
                'default_mode' => 'solo',
                'min_stake' => 0.5,
                'max_stake' => 30,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 300,
                    'type' => 'puzzle',
                    'categories' => ['riddles', 'lateral', 'visual'],
                ],
            ],
            [
                'name' => 'Number Sequence',
                'slug' => 'number-sequence',
                'description' => 'Trouvez le nombre suivant - Suites logiques',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.3,
                'max_stake' => 20,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 180,
                    'type' => 'logic',
                    'rounds' => 15,
                ],
            ],
            [
                'name' => 'Spatial Reasoning',
                'slug' => 'spatial-reasoning',
                'description' => 'Rotation mentale et visualisation 3D - Intelligence spatiale',
                'min_players' => 1,
                'max_players' => 2,
                'default_mode' => 'solo',
                'min_stake' => 0.5,
                'max_stake' => 25,
                'is_active' => true,
                'requires_rng' => false,
                'settings' => [
                    'duration_seconds' => 240,
                    'type' => 'spatial',
                    'rounds' => 12,
                ],
            ],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['slug' => $game['slug']],
                $game
            );
        }
    }
}

