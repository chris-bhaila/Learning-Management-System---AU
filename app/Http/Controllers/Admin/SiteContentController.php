<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteContentRequest;
use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Support\Arr;

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
        $this->content->update(Arr::dot($request->validated('content')));

        return back()->with('success', 'Landing page content updated.');
    }
}
