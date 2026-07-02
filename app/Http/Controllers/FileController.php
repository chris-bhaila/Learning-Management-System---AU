<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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
        $this->authorize('download', $file);

        return Storage::disk('private')->download($file->path, $file->original_name);
    }

    public function viewToken(int $id)
    {
        $file = $this->files->find($id);
        abort_if(is_null($file), 404);
        $this->authorize('download', $file);

        $url = URL::temporarySignedRoute(
            'files.raw',
            now()->addMinutes(5),
            ['id' => $id],
        );

        return response()->json(['url' => $url]);
    }

    public function raw(int $id)
    {
        $file = $this->files->find($id);
        abort_if(is_null($file), 404);
        $this->authorize('download', $file);

        return Storage::disk('private')->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
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