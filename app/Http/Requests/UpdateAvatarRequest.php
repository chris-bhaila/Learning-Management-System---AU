<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained check (self or admin acting on another user) happens
        // in the controller via $this->authorize('updateAvatar', $targetUser).
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                // gif excluded (large files, less useful as profile photos).
                // svg excluded — SVGs can carry embedded scripts (XSS vector).
                'mimes:jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.mimes' => 'Only JPEG, PNG, and WebP images are accepted.',
            'avatar.max'   => 'The image must be 2 MB or smaller.',
        ];
    }
}
