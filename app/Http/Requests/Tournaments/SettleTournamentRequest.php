<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

class SettleTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1'],
            'results.*.entry_id' => ['required', 'integer', 'exists:tournament_entries,id'],
            'results.*.placement' => ['required', 'integer', 'min:1'],
            'results.*.payout_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}

