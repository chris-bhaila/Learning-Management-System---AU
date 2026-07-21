<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteContentRequest;
use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class SiteContentController extends Controller
{
    public function __construct(
        private SiteContentRepositoryInterface $content,
    ) {}

    public function edit()
    {
        return view('admin.site-content.edit', [
            'content' => $this->content->all(),
        ]);
    }

    public function update(UpdateSiteContentRequest $request)
    {
        // Arr::dot() reverses the form's nested content[hero][heading_line1] structure
        // back into the flat "hero.heading_line1" style the site_content table's
        // key column uses.
        $data = Arr::dot($request->validated('content'));

        $existingImage = $this->content->get('hero.image');

        if ($request->hasFile('hero_image')) {
            $data['hero.image'] = $this->processAndStore($request);

            if ($existingImage) {
                Storage::disk('public')->delete($existingImage);
            }
        } elseif ($request->boolean('remove_hero_image')) {
            if ($existingImage) {
                Storage::disk('public')->delete($existingImage);
            }

            $data['hero.image'] = '';
        }

        $this->content->update($data);

        return back()->with('success', 'Landing page content updated.');
    }

    private function processAndStore(UpdateSiteContentRequest $request): string
    {
        $manager = new ImageManager(new Driver());
        $image   = $manager->decode($request->file('hero_image')->getRealPath());
        $image->cover(1920, 1080);
        $encoded = $image->encode(new WebpEncoder(85));

        $filename = Str::uuid() . '.webp';
        $path     = 'site-content/' . $filename;
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
