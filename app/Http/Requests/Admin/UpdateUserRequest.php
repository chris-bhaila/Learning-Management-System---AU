<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        // Email is intentionally absent — never submitted, never reaches the repository.
        return [
            'name'          => ['required', 'string', 'max:255'],
            'role'          => ['required', 'string', 'in:teacher,student'],
            'is_active'     => ['required', 'boolean'],
            // Avatar fields are optional — present only when admin uploads or removes.
            // gif/svg excluded: gif is large and unhelpful; svg can carry embedded scripts.
            'avatar'        => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
