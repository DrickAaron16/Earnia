<?php

namespace App\Http\Requests\Matchmaking;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'mode' => ['required', 'in:solo,duel,multiplayer'],
            'stake_amount' => ['required', 'numeric', 'min:0.5'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:10'],
            'skill_rating' => ['nullable', 'integer'],
            'filters' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $gameId = $this->input('game_id');
            $stakeAmount = (float) $this->input('stake_amount');

            if (! $gameId || ! $stakeAmount) {
                return;
            }

            $game = Game::find($gameId);

            if (! $game) {
                return;
            }

            if ($stakeAmount < (float) $game->min_stake) {
                $validator->errors()->add('stake_amount', __('Stake below minimum for this game.'));
            }

            if ($game->max_stake && $stakeAmount > (float) $game->max_stake) {
                $validator->errors()->add('stake_amount', __('Stake exceeds maximum for this game.'));
            }
        });
    }
}

