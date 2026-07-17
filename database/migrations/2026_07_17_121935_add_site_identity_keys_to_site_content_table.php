<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The original create_site_content_table migration already seeds every key in
     * SiteContent::DEFAULTS on fresh installs — this only backfills the two new
     * 'site.name'/'site.short_label' keys added later, for databases that already
     * ran that migration before this addendum. updateOrInsert() keeps it safe to
     * run against a database that (for any reason) already has these rows.
     */
    public function up(): void
    {
        $now = now();

        foreach (['site.name', 'site.short_label'] as $key) {
            DB::table('site_content')->updateOrInsert(
                ['key' => $key],
                ['value' => SiteContent::DEFAULTS[$key], 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_content')->whereIn('key', ['site.name', 'site.short_label'])->delete();
    }
};
