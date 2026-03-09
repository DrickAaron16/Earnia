<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entry_fee' => ['required', 'numeric', 'min:0'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'max_players' => ['nullable', 'integer', 'min:2'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after_or_equal:registration_starts_at'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'rules' => ['nullable', 'array'],
            'payout_structure' => ['nullable', 'array'],
        ];
    }
}

