<?php

namespace App\Models\Concerns;

use App\Models\File;
use Illuminate\Support\Collection;

trait GroupsFilesByDate
{
    /**
     * Files grouped by calendar day (created_at date), newest date first.
     * Relies on the files() relation already being ordered newest-first,
     * so both group order and within-group order fall out for free.
     */
    public function filesGroupedByDate(): Collection
    {
        return $this->files->groupBy(fn (File $file) => $file->created_at->toDateString());
    }
}
