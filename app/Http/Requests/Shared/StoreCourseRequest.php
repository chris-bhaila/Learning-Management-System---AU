<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'group_id'     => ['nullable', 'integer', 'exists:course_groups,id'],
            'is_published' => ['boolean'],
            'files'        => ['nullable', 'array'],
            'files.*'      => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,png,jpg,jpeg,zip'],
        ];
    }
}