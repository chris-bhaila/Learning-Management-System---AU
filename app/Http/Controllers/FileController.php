<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        private FileRepositoryInterface $files,
    ) {}

    public function store(StoreFileRequest $request)
    {
        $this->files->storeUploads(
            [$request->file('file')],
            $request->validated('fileable_type'),
            $request->validated('fileable_id'),
            Auth::id(),
        );

        return back()->with('success', 'File uploaded.');
    }

    public function download(int $id)
    {
        $file = $this->files->find($id);

        // A student can reach this from a permanent notification snapshot that outlives
        // the file itself (File uses SoftDeletes, so a deleted file's row is simply gone
        // from find()'s default scope) — a friendly flash, not a crash (authorize() below
        // would TypeError on a null model) or a bare 404 with no explanation.
        if (is_null($file)) {
            return back()->with('error', 'This file has been removed by the teacher.');
        }

        $this->authorize('download', $file);

        return Storage::disk('private')->download($file->path, $file->original_name);
    }

    public function destroy(int $id)
    {
        $file = $this->files->find($id);
        $this->authorize('delete', $file);

        Storage::disk('private')->delete($file->path);
        $this->files->delete($file);

        return back()->with('success', 'File deleted.');
    }
}