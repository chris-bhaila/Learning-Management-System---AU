<?php

namespace App\Repositories\Eloquent;

use App\Models\File;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function storeUploads(array $files, string $fileableType, int $fileableId, int $uploadedBy): void
    {
        foreach ($files as $uploaded) {
            $filename = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
            $path     = $uploaded->storeAs('uploads', $filename, 'private');

            $this->create([
                'fileable_type' => $fileableType,
                'fileable_id'   => $fileableId,
                'filename'      => $filename,
                'original_name' => $uploaded->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $uploaded->getMimeType(),
                'size'          => $uploaded->getSize(),
                'uploaded_by'   => $uploadedBy,
            ]);
        }
    }
}