<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->foreignId('log_id')->change()->constrained('activity_log')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
        });
    }
};
