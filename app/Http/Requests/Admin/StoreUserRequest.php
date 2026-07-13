<?php

namespace App\Http\Requests\Admin;

use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->isAdmin()) {
            return false;
        }

        // A crafted request choosing role=admin must be rejected (403) here — independent
        // of the UI, and independent of the format rule in rules() below — for anyone who
        // isn't Super Admin. Reuses UserPolicy::canAssignRole(), the same check
        // promoteToAdmin() uses, so "who can grant admin" lives in exactly one place.
        if ($this->input('role') === 'admin') {
            return app(UserPolicy::class)->canAssignRole($this->user(), 'admin');
        }

        return true;
    }

    public function rules(): array
    {
        // 'admin' is only a valid format for Super Admin — authorize() above already
        // rejects (403) anyone else submitting it, but the format rule must independently
        // agree, or a genuine Super Admin's legitimate role=admin submission would fail
        // validation (422) right after passing authorization.
        $allowedRoles = $this->user()->isSuperAdmin() ? 'teacher,student,admin' : 'teacher,student';

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'                  => ['required', 'string', "in:{$allowedRoles}"],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
