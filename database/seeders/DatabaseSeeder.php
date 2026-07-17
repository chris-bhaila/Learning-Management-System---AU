<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ────────────────────────────────────────────────
        $roles = [];
        foreach (['super_admin', 'admin', 'teacher', 'student'] as $name) {
            $roles[$name] = Role::firstOrCreate(['name' => $name]);
        }

        // Password convention: lowercase name (no spaces) + "123"
        $password = fn(string $name) => Hash::make(
            strtolower(str_replace(' ', '', $name)) . '123'
        );

        // role_id/is_active are deliberately excluded from User::$fillable (see the
        // model) — updateOrCreate()'s mass-assigned $attributes would silently drop
        // both, so they're set via explicit attribute assignment on the resolved row
        // instead, same as EloquentUserRepository::create()/update().
        $upsertUser = function (string $email, string $name, int $roleId, string $password) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'name'     => $name,
                'password' => $password,
            ]);
            $user->role_id   = $roleId;
            $user->is_active = true;
            $user->save();

            return $user;
        };

        // ── Super Admin (1) ──────────────────────────────────────
        // Deliberately singular — assigned once at handover, not a role admins or
        // teachers can grant to each other. There is no UI or self-service path that
        // creates a super_admin; this seeder entry is the only place one is ever created.
        // Excluded from Google OAuth (see GoogleController) and never appears in the
        // generic role-select UI (see UpdateUserRequest / StoreUserRequest).
        $upsertUser('superadmin@edunest.dev', 'Sam Super', $roles['super_admin']->id, $password('Sam Super'));

        // ── Admin (1) ────────────────────────────────────────────
        $upsertUser('admin@edunest.dev', 'Alex Admin', $roles['admin']->id, $password('Alex Admin'));

        // ── Teachers (3) ─────────────────────────────────────────
        $teachers = [
            ['name' => 'Taylor Reed',  'email' => 'taylor.reed@edunest.dev'],
            ['name' => 'Morgan Blake', 'email' => 'morgan.blake@edunest.dev'],
            ['name' => 'Jordan Hayes', 'email' => 'jordan.hayes@edunest.dev'],
        ];

        foreach ($teachers as $data) {
            $upsertUser($data['email'], $data['name'], $roles['teacher']->id, $password($data['name']));
        }

        // ── Students (9) ─────────────────────────────────────────
        $students = [
            ['name' => 'Emma Wilson',     'email' => 'emma.wilson@edunest.dev'],
            ['name' => 'Liam Carter',     'email' => 'liam.carter@edunest.dev'],
            ['name' => 'Sophia Davis',    'email' => 'sophia.davis@edunest.dev'],
            ['name' => 'Noah Martinez',   'email' => 'noah.martinez@edunest.dev'],
            ['name' => 'Olivia Thompson', 'email' => 'olivia.thompson@edunest.dev'],
            ['name' => 'Ethan Brown',     'email' => 'ethan.brown@edunest.dev'],
            ['name' => 'Ava Johnson',     'email' => 'ava.johnson@edunest.dev'],
            ['name' => 'Mason Lee',       'email' => 'mason.lee@edunest.dev'],
            ['name' => 'Isabella White',  'email' => 'isabella.white@edunest.dev'],
        ];

        foreach ($students as $data) {
            $upsertUser($data['email'], $data['name'], $roles['student']->id, $password($data['name']));
        }
    }
}
