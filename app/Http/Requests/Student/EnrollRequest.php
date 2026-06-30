<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStudent();
    }

    public function rules(): array
    {
        return [
            'token_value' => ['required', 'string', 'min:6', 'max:9', 'regex:/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'token_value.required' => 'Please enter an enrollment token.',
            'token_value.min'      => 'Tokens are at least 6 characters.',
            'token_value.max'      => 'Tokens are at most 9 characters.',
            'token_value.regex'    => 'Token contains invalid characters.',
        ];
    }
}