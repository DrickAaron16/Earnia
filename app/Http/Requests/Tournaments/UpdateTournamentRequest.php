<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'entry_fee' => ['sometimes', 'numeric', 'min:0'],
            'prize_pool' => ['sometimes', 'numeric', 'min:0'],
            'max_players' => ['sometimes', 'nullable', 'integer', 'min:2'],
            'registration_starts_at' => ['sometimes', 'nullable', 'date'],
            'registration_ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:registration_starts_at'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'rules' => ['sometimes', 'nullable', 'array'],
            'payout_structure' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'in:draft,upcoming,running,completed,cancelled'],
        ];
    }
}

