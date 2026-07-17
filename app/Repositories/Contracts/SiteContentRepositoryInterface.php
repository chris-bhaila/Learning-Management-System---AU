<?php

namespace App\Repositories\Contracts;

interface SiteContentRepositoryInterface
{
    /** A single value by key, falling back to $default (or the built-in
     *  SiteContent::DEFAULTS entry) if the key doesn't exist. */
    public function get(string $key, ?string $default = null): string;

    /** All known keys => values, DB rows merged over SiteContent::DEFAULTS so a
     *  missing/deleted row never renders blank on the landing page. */
    public function all(): array;

    /** Bulk upsert from the admin edit form — $data is key => value. */
    public function update(array $data): void;
}
