<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Str;
use RuntimeException;

class TournamentService
{
    public function create(array $data): Tournament
    {
        $game = Game::findOrFail($data['game_id']);

        if ($data['entry_fee'] < $game->min_stake) {
            throw new RuntimeException('Entry fee below game minimum stake.');
        }

        if ($game->max_stake && $data['entry_fee'] > $game->max_stake) {
            throw new RuntimeException('Entry fee exceeds game maximum stake.');
        }

        return Tournament::create([
            'game_id' => $game->id,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'entry_fee' => $data['entry_fee'],
            'prize_pool' => $data['prize_pool'] ?? 0,
            'max_players' => $data['max_players'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'registration_starts_at' => $data['registration_starts_at'] ?? null,
            'registration_ends_at' => $data['registration_ends_at'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'rules' => $data['rules'] ?? null,
            'payout_structure' => $data['payout_structure'] ?? null,
        ]);
    }

    public function publish(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'draft') {
            throw new RuntimeException('Only draft tournaments can be published.');
        }

        $tournament->update([
            'status' => 'upcoming',
        ]);

        return $tournament;
    }

    public function cancel(Tournament $tournament, ?string $reason = null): Tournament
    {
        if (in_array($tournament->status, ['completed', 'cancelled'], true)) {
            throw new RuntimeException('Tournament already finished.');
        }

        $rules = $tournament->rules ?? [];
        $rules['cancel_reason'] = $reason;

        $tournament->update([
            'status' => 'cancelled',
            'rules' => $rules,
        ]);

        return $tournament;
    }
}

