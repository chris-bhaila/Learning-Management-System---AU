<?php

namespace App\Repositories\Contracts;

use App\Models\File;
use Illuminate\Database\Eloquent\Collection;

interface FileRepositoryInterface
{
    public function find(int $id): ?File;
    public function create(array $data): File;
    public function delete(File $file): bool;
    public function getByFileable(string $type, int $id): Collection;

    /** Store one or more uploaded files and create DB records for each. */
    public function storeUploads(array $files, string $fileableType, int $fileableId, int $uploadedBy): void;
}