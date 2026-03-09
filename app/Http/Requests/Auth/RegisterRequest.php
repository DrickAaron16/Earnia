<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:32', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users,phone_number'],
            'country_code' => ['nullable', 'string', 'max:4'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'currency' => ['nullable', 'string', 'size:3'],
            'pseudo' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $birthDate = $this->date('birth_date') ?? $this->date('date_of_birth');

            if ($birthDate && $birthDate->diffInYears(now()) < 18) {
                $validator->errors()->add('birth_date', __('You must be at least 18 years old.'));
                $validator->errors()->add('date_of_birth', __('You must be at least 18 years old.'));
            }
        });
    }
}

