<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'content'      => ['nullable', 'string'],
            'order'        => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ];
    }
}