<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_content';

    protected $fillable = ['key', 'value'];

    /** Rendered wherever `hero.image` is blank — both the public landing page and
     *  the admin preview must agree on what "no image uploaded yet" looks like. */
    public const DEFAULT_HERO_IMAGE_URL = 'https://images.unsplash.com/photo-1606761568499-6d2451b23c66?q=80&w=1374&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';

    /**
     * Every editable landing-page string, keyed the same way as the `site_content.key`
     * column, with the copy that was hardcoded in resources/views/landing.blade.php
     * before this became admin-editable. Used both to seed the table (migration) and
     * as the fallback in EloquentSiteContentRepository::all() if a row is ever missing
     * (e.g. deleted directly from the DB) — the landing page must never render blank.
     */
    public const DEFAULTS = [
        'site.name'         => 'EduNest',
        'site.short_label'  => 'EN',

        'nav.sign_in_label' => 'Sign in',

        'hero.badge'        => 'Private · Instructor-Led · Focused',
        'hero.heading_line1' => 'A focused space to',
        'hero.heading_line2' => 'learn, not wander.',
        'hero.subheading'   => "EduNest connects students directly with their instructor's courses — no marketplace noise, no subscriptions. Just structured, intentional learning.",
        'hero.cta_label'    => 'Get Started',
        'hero.caption'      => 'First login creates your student account automatically.',
        // Empty by default — landing.blade.php falls back to the original stock photo
        // when this is blank, so a fresh install never renders a broken background.
        'hero.image'        => '',

        'features.eyebrow'  => 'What EduNest Offers',
        'features.heading'  => 'Built for real learning',

        'feature.1.title'       => 'Student-Centered Learning',
        'feature.1.description' => 'Access your enrolled courses, track progress through units, and download materials — all inside a clean, distraction-free workspace.',
        'feature.2.title'       => 'Teacher-Led Success',
        'feature.2.description' => 'Instructors build courses, organise units, and share secure enrollment tokens — keeping every class intentional and private.',
        'feature.3.title'       => 'Private & Secure',
        'feature.3.description' => 'No public marketplace. Students enroll only via a token from their instructor. Content is protected behind role-based access control.',

        'how_it_works.eyebrow'  => 'Simple by Design',
        'how_it_works.heading'  => 'How It Works',

        'how_it_works.1.title'       => 'Join',
        'how_it_works.1.description' => 'Sign in with your Google account. Your student profile is created automatically on first login — no passwords, no forms.',
        'how_it_works.2.title'       => 'Learn',
        'how_it_works.2.description' => 'Enter the enrollment token your instructor gave you to unlock access to all course materials and units.',
        'how_it_works.3.title'       => 'Succeed',
        'how_it_works.3.description' => 'Progress through units at your pace, download resources, and stay on top of your learning journey.',

        'footer.link.privacy' => 'Privacy',
        'footer.link.terms'   => 'Terms',
        'footer.link.support' => 'Support',
        'footer.copyright'    => 'EduNest. All rights reserved.',
    ];
}
