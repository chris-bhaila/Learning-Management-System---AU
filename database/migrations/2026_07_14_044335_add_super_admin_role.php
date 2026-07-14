<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // roles.name is unique (see create_roles_table), so insertOrIgnore is a safe
        // no-op if this row already exists (e.g. previously created by DatabaseSeeder).
        DB::table('roles')->insertOrIgnore([
            'name'       => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'super_admin')->first();

        if (!$role) {
            return;
        }

        $inUse = DB::table('users')->where('role_id', $role->id)->exists();

        if ($inUse) {
            throw new \RuntimeException(
                'Cannot roll back add_super_admin_role: one or more users still reference '
                . 'the super_admin role. Reassign or remove those users first.'
            );
        }

        DB::table('roles')->where('id', $role->id)->delete();
    }
};
