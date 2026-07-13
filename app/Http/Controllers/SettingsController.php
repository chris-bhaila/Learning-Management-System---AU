<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class SettingsController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function index()
    {
        $layout = 'layouts.' . Auth::user()->panelRoleName();

        return view('settings.index', compact('layout'));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $this->users->update($user, $request->validated());

        return back()->with('success', 'Name updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();
        $this->users->update($user, [
            'password' => $request->validated('password'), // hashed by User model cast
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateAvatar(UpdateAvatarRequest $request)
    {
        $user = Auth::user();
        $this->authorize('updateAvatar', $user);

        $path = $this->processAndStore($request);
        $this->users->updateAvatar($user, $path);

        return back()->with('success', 'Avatar updated successfully.');
    }

    public function removeAvatar(Request $request)
    {
        $user = Auth::user();
        $this->authorize('removeAvatar', $user);

        $this->users->removeAvatar($user);

        return back()->with('success', 'Avatar removed.');
    }

    private function processAndStore(UpdateAvatarRequest $request): string
    {
        $manager  = new ImageManager(new Driver());
        $image    = $manager->decode($request->file('avatar')->getRealPath());
        $image->cover(256, 256);
        $encoded  = $image->encode(new WebpEncoder(90));

        $filename = Str::uuid() . '.webp';
        $path     = 'avatars/' . $filename;
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
