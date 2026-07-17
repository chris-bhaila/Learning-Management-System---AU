<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guards both expiry-notification triggers (max-uses, checked synchronously right
     * after incrementUses(); time-limit, checked by the tokens:notify-expired schedule)
     * against ever logging the same token's expiry twice.
     */
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->boolean('expiry_notified')->default(false)->after('uses_count');
        });
    }

    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('expiry_notified');
        });
    }
};
