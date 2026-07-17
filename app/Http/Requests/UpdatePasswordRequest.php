<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any account with a linked Google account has no password to change,
        // permanently — regardless of role, password history, or how this
        // particular session authenticated. Blocked here so a crafted request
        // is rejected even if the Blade conditional hiding the form is bypassed.
        return auth()->check() && is_null(auth()->user()->google_id);
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
