<?php

namespace App\Http\Requests\Matchmaking;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MatchmakingTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', Rule::exists('games', 'id')->where('is_active', true)],
            'mode' => ['required', Rule::in(['duel', 'multiplayer'])],
            'stake_amount' => ['required', 'numeric', 'min:0.10'],
            'max_players' => ['nullable', 'integer', 'min:2', 'max:10'],
            'skill_rating' => ['nullable', 'integer', 'min:1'],
            'filters' => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'max_players' => $this->input('max_players', 2),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $game = Game::query()->find($this->input('game_id'));
            if (! $game) {
                return;
            }

            $stake = (float) $this->input('stake_amount');
            if ($stake < (float) $game->min_stake) {
                $validator->errors()->add('stake_amount', __('Stake below minimum for this game.'));
            }

            if ($game->max_stake !== null && $stake > (float) $game->max_stake) {
                $validator->errors()->add('stake_amount', __('Stake above maximum for this game.'));
            }

            $maxPlayers = (int) $this->input('max_players', 2);
            if ($maxPlayers > $game->max_players) {
                $validator->errors()->add('max_players', __('Too many players for this game.'));
            }
        });
    }
}

