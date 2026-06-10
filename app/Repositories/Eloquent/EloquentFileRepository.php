<?php

namespace App\Repositories\Eloquent;

use App\Models\File;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentFileRepository implements FileRepositoryInterface
{
    public function find(int $id): ?File
    {
        return File::find($id);
    }

    public function create(array $data): File
    {
        return File::create($data);
    }

    public function delete(File $file): bool
    {
        return $file->delete();
    }

    public function getByFileable(string $type, int $id): Collection
    {
        return File::where('fileable_type', $type)
            ->where('fileable_id', $id)
            ->get();
    }
}