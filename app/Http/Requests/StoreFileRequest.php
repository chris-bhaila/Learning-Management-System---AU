<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'file'          => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,png,jpg,jpeg,zip',
            ],
            'fileable_type' => ['required', 'string', 'in:App\Models\Course,App\Models\Unit'],
            'fileable_id'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'   => 'File size must not exceed 20MB.',
            'file.mimes' => 'File type not allowed.',
        ];
    }
}