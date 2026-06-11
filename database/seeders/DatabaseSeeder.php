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
        foreach (['admin', 'teacher', 'student'] as $name) {
            $roles[$name] = Role::firstOrCreate(['name' => $name]);
        }

        // Password convention: lowercase name (no spaces) + "123"
        $password = fn(string $name) => Hash::make(
            strtolower(str_replace(' ', '', $name)) . '123'
        );

        // ── Admin (1) ────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@edunest.dev'],
            [
                'name'      => 'Alex Admin',
                'role_id'   => $roles['admin']->id,
                'password'  => $password('Alex Admin'),
                'is_active' => true,
            ]
        );

        // ── Teachers (3) ─────────────────────────────────────────
        $teachers = [
            ['name' => 'Taylor Reed',  'email' => 'taylor.reed@edunest.dev'],
            ['name' => 'Morgan Blake', 'email' => 'morgan.blake@edunest.dev'],
            ['name' => 'Jordan Hayes', 'email' => 'jordan.hayes@edunest.dev'],
        ];

        foreach ($teachers as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'role_id'   => $roles['teacher']->id,
                    'password'  => $password($data['name']),
                    'is_active' => true,
                ]
            );
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
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'role_id'   => $roles['student']->id,
                    'password'  => $password($data['name']),
                    'is_active' => true,
                ]
            );
        }
    }
}
