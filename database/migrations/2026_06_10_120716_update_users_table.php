<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete()->after('id');
            $table->string('google_id')->unique()->after('role_id');
            $table->string('avatar')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->softDeletes();

            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'google_id', 'avatar', 'is_active', 'deleted_at']);

            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};