<?php

namespace App\Http\Requests\GameSessions;

use Illuminate\Foundation\Http\FormRequest;

class SubmitScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:0'],
        ];
    }
}

