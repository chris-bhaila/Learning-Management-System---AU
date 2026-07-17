<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Field names are submitted nested (content[hero][heading_line1], etc.) so
     * Laravel's dot-notation validation targets them naturally; the controller
     * flattens the validated array back to site_content's flat "hero.heading_line1"
     * key style via Arr::dot() before handing it to the repository.
     *
     * Every field has a genuinely restrictive max: length, sized to where it
     * actually renders (short badges/labels vs. paragraph-length descriptions),
     * per the project's Form Request conventions.
     */
    public function rules(): array
    {
        return [
            'content.site.name'        => ['required', 'string', 'max:40'],
            'content.site.short_label' => ['required', 'string', 'max:4'],

            'content.nav.sign_in_label' => ['required', 'string', 'max:30'],

            'content.hero.badge'         => ['required', 'string', 'max:60'],
            'content.hero.heading_line1' => ['required', 'string', 'max:60'],
            'content.hero.heading_line2' => ['required', 'string', 'max:60'],
            'content.hero.subheading'    => ['required', 'string', 'max:300'],
            'content.hero.cta_label'     => ['required', 'string', 'max:30'],
            'content.hero.caption'       => ['required', 'string', 'max:150'],

            'content.features.eyebrow' => ['required', 'string', 'max:60'],
            'content.features.heading' => ['required', 'string', 'max:80'],

            'content.feature.1.title'       => ['required', 'string', 'max:60'],
            'content.feature.1.description' => ['required', 'string', 'max:200'],
            'content.feature.2.title'       => ['required', 'string', 'max:60'],
            'content.feature.2.description' => ['required', 'string', 'max:200'],
            'content.feature.3.title'       => ['required', 'string', 'max:60'],
            'content.feature.3.description' => ['required', 'string', 'max:200'],

            'content.how_it_works.eyebrow' => ['required', 'string', 'max:60'],
            'content.how_it_works.heading' => ['required', 'string', 'max:80'],

            'content.how_it_works.1.title'       => ['required', 'string', 'max:40'],
            'content.how_it_works.1.description' => ['required', 'string', 'max:200'],
            'content.how_it_works.2.title'       => ['required', 'string', 'max:40'],
            'content.how_it_works.2.description' => ['required', 'string', 'max:200'],
            'content.how_it_works.3.title'       => ['required', 'string', 'max:40'],
            'content.how_it_works.3.description' => ['required', 'string', 'max:200'],

            'content.footer.link.privacy' => ['required', 'string', 'max:30'],
            'content.footer.link.terms'   => ['required', 'string', 'max:30'],
            'content.footer.link.support' => ['required', 'string', 'max:30'],
            'content.footer.copyright'    => ['required', 'string', 'max:100'],
        ];
    }
}
