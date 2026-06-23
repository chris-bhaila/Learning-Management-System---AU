<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'teacher_id'   => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (!$user?->isTeacher()) {
                        $fail('The selected user must have the teacher role.');
                    }
                },
            ],
            'group_id'     => ['nullable', 'integer', 'exists:course_groups,id'],
            'is_published' => ['boolean'],
        ];
    }
}
