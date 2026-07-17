<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinct from expires_at — a manually revoked token and a naturally time-expired
     * token are different events with different causes and different teacher-facing
     * notification messages (see EloquentTokenRepository::logRevocation()/logExpiry()).
     * A revoked token is no longer hard-deleted immediately; only the widened
     * tokens:prune job (180 days) deletes the row, eventually, same as natural expiry.
     */
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('expiry_notified');
        });
    }

    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
