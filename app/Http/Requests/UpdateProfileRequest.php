<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Email is intentionally absent — never submitted, never reaches the repository.
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
