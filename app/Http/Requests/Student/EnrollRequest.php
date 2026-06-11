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
            'token_value' => ['required', 'string', 'min:8', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'token_value.required' => 'Please enter an enrollment token.',
        ];
    }
}