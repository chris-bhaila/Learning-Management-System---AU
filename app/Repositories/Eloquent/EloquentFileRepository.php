<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Models\File;
use App\Models\Unit;
use App\Models\User;
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
        // Loaded once per call, not per file — same fileable/uploader for the whole batch.
        $fileable = $fileableType::find($fileableId);
        $uploader = User::find($uploadedBy);

        foreach ($files as $uploaded) {
            $filename = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
            $path     = $uploaded->storeAs('uploads', $filename, 'private');

            $file = $this->create([
                'fileable_type' => $fileableType,
                'fileable_id'   => $fileableId,
                'filename'      => $filename,
                'original_name' => $uploaded->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $uploaded->getMimeType(),
                'size'          => $uploaded->getSize(),
                'uploaded_by'   => $uploadedBy,
            ]);

            $this->logUpload($file, $fileable, $uploader);
        }
    }

    /** Distinct descriptions for course-level vs unit-level uploads (mirrors the
     *  class/course split used for token notifications) — logged at the moment of a
     *  successful upload, capturing plain scalars (file_name, file_type, teacher_name,
     *  course_name/unit_name), never live FK lookups, so the notification stays fully
     *  readable even after the file (or later, the course/unit) is deleted. */
    private function logUpload(File $file, Course|Unit|null $fileable, ?User $uploader): void
    {
        $isUnit = $fileable instanceof Unit;
        $course = $isUnit ? $fileable->course : $fileable;

        $properties = [
            'file_id'      => $file->id,
            'file_name'    => $file->original_name,
            'file_type'    => $file->mime_type,
            'teacher_name' => $uploader?->name ?? 'Your teacher',
            'course_id'    => $course?->id,
            'course_name'  => $course?->title ?? 'Unknown',
        ];

        if ($isUnit) {
            $properties['unit_id']   = $fileable->id;
            $properties['unit_name'] = $fileable->title;
        }

        activity()
            ->causedBy($uploader)
            ->withProperties($properties)
            ->log($isUnit ? 'File uploaded to unit' : 'File uploaded to course');
    }
}