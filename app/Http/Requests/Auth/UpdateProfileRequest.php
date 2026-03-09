<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'username' => ['sometimes', 'string', 'alpha_dash', 'min:3', 'max:32', "unique:users,username,{$userId}"],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20', "unique:users,phone_number,{$userId}"],
            'country_code' => ['sometimes', 'nullable', 'string', 'max:4'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
        ];
    }
}

