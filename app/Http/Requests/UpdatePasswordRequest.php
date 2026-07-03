<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Google-only accounts have no password yet — current_password is only
        // required once a password already exists to verify (set vs change).
        $hasPassword = ! is_null($this->user()->password);

        return [
            'current_password' => $hasPassword ? ['required', 'current_password'] : ['nullable'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
