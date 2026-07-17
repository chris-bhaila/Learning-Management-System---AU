<?php

namespace App\Repositories\Eloquent;

use App\Models\SiteContent;
use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class EloquentSiteContentRepository implements SiteContentRepositoryInterface
{
    /** The public landing page is the most-hit unauthenticated route in the app and
     *  every render needs all ~27 keys — cached indefinitely (content changes only
     *  via the admin form, which explicitly invalidates this on save) rather than
     *  re-querying the table on every anonymous hit. */
    private const CACHE_KEY = 'site_content.all';

    public function get(string $key, ?string $default = null): string
    {
        return $this->all()[$key] ?? $default ?? SiteContent::DEFAULTS[$key] ?? '';
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = SiteContent::query()->pluck('value', 'key')->all();

            // DB values win where present; any key missing from the table
            // (deleted, or a default never seeded) still renders its default.
            return array_merge(SiteContent::DEFAULTS, $stored);
        });
    }

    public function update(array $data): void
    {
        foreach ($data as $key => $value) {
            SiteContent::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
