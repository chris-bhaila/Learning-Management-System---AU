<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_content', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        // Seeded here (not only in a separate seeder) so a bare `php artisan migrate`
        // alone guarantees the landing page renders identically to the previously
        // hardcoded copy — no dependency on remembering to also run `db:seed`.
        $now = now();
        $rows = collect(SiteContent::DEFAULTS)->map(fn ($value, $key) => [
            'key'        => $key,
            'value'      => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        DB::table('site_content')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_content');
    }
};
