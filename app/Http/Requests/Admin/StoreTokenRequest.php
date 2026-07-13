<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'teacher_id'     => ['required', 'integer', 'exists:users,id'],
            'type'           => ['required', 'string', 'in:class,course'],
            'course_id'      => ['nullable', 'integer', 'exists:courses,id', 'required_if:type,course'],
            'lifetime_value' => ['required', 'integer', 'min:1'],
            'lifetime_unit'  => ['required', 'string', 'in:minutes,hours'],
            'max_uses'       => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required_if' => 'A course must be selected for course tokens.',
        ];
    }

    public function lifetimeInMinutes(): int
    {
        $value = $this->validated('lifetime_value');
        $unit  = $this->validated('lifetime_unit');

        return $unit === 'hours' ? $value * 60 : $value;
    }
}
