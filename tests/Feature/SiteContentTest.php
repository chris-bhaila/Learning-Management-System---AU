<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\Role;
use App\Models\SiteContent;
use App\Models\User;
use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Covers the admin-editable landing page content feature: the site_content
 * table/repository (App\Repositories\Eloquent\EloquentSiteContentRepository),
 * Admin\SiteContentController, and resources/views/landing.blade.php reading
 * from it instead of hardcoded strings.
 */
class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The real seed data lives in the migration itself (not a separate
        // seeder), so it's already present after RefreshDatabase's migrate.
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->student()->create();
    }

    // ─── Migration + seed correctness ──────────────────────────────────

    public function test_migration_seeds_every_default_key(): void
    {
        $this->assertSame(count(SiteContent::DEFAULTS), SiteContent::count());

        foreach (SiteContent::DEFAULTS as $key => $value) {
            $this->assertDatabaseHas('site_content', ['key' => $key, 'value' => $value]);
        }
    }

    public function test_landing_page_renders_every_seeded_string_before_any_edit(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        foreach (SiteContent::DEFAULTS as $value) {
            // e() mirrors Blade's {{ }} escaping — some default strings (e.g. the
            // apostrophe in hero.subheading) render HTML-entity-encoded, same as
            // any other plain-text field would.
            $response->assertSee(e($value), false);
        }
    }

    // ─── Fallback behavior when a key is missing ───────────────────────

    public function test_repository_falls_back_to_default_when_a_key_is_deleted(): void
    {
        SiteContent::where('key', 'hero.cta_label')->delete();

        /** @var SiteContentRepositoryInterface $repo */
        $repo = app(SiteContentRepositoryInterface::class);

        $this->assertSame(SiteContent::DEFAULTS['hero.cta_label'], $repo->get('hero.cta_label'));
        $this->assertNotSame('', $repo->get('hero.cta_label'));
    }

    public function test_landing_page_still_renders_the_button_text_when_its_row_is_deleted(): void
    {
        SiteContent::where('key', 'hero.cta_label')->delete();

        $response = $this->get(route('home'));

        $response->assertOk();
        // Falls back to the default rather than rendering blank or erroring.
        $response->assertSee(SiteContent::DEFAULTS['hero.cta_label']);
    }

    // ─── Admin access control ──────────────────────────────────────────

    public function test_admin_can_view_the_edit_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.site-content.edit'));

        $response->assertOk();
        $response->assertSee('Landing Page Content');
        // A representative field from each group, to confirm the form is populated
        // from the repository rather than rendering blank inputs.
        $response->assertSee(SiteContent::DEFAULTS['hero.heading_line1'], false);
    }

    // AdminMiddleware aborts these with a 403 AuthorizationException, but this app's
    // global exception handler (bootstrap/app.php) converts a non-JSON 403 into a
    // redirect to the user's own dashboard with a flashed error, rather than a raw
    // 403 body — the same convention every other admin-access test in this suite
    // follows (see GoogleLoginTest, DemoteAdminTest, CreateAdminAccountTest). What
    // matters here is that the teacher/student is NOT granted access to the form.
    public function test_teacher_cannot_access_the_edit_form(): void
    {
        $response = $this->actingAs($this->teacher())->get(route('admin.site-content.edit'));
        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_student_cannot_access_the_edit_form(): void
    {
        $response = $this->actingAs($this->student())->get(route('admin.site-content.edit'));
        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_guest_cannot_access_the_edit_form(): void
    {
        $response = $this->get(route('admin.site-content.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_submit_an_update(): void
    {
        $response = $this->actingAs($this->teacher())
            ->patch(route('admin.site-content.update'), $this->validPayload());

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertSame(
            SiteContent::DEFAULTS['hero.heading_line1'],
            SiteContent::where('key', 'hero.heading_line1')->value('value')
        );
    }

    public function test_guest_cannot_submit_an_update(): void
    {
        $response = $this->patch(route('admin.site-content.update'), $this->validPayload());
        $response->assertRedirect(route('login'));
    }

    // ─── Editing, escaping, and cache invalidation ─────────────────────

    public function test_admin_edit_is_reflected_on_the_public_landing_page_and_escaped(): void
    {
        $payload = $this->validPayload();
        // An apostrophe AND an HTML-like injection attempt in the same edit.
        $payload['content']['hero']['heading_line1'] = "O'Brien's <script>alert(1)</script> Academy";

        $response = $this->actingAs($this->admin())
            ->patch(route('admin.site-content.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(
            "O'Brien's <script>alert(1)</script> Academy",
            SiteContent::where('key', 'hero.heading_line1')->value('value')
        );

        // Log out first — the "home" route redirects an authenticated user to their
        // own dashboard, so this must be a genuinely unauthenticated request to
        // actually exercise the public landing page.
        $this->post(route('logout'));
        $this->assertGuest();

        // Public, unauthenticated view of the landing page — content must have
        // changed (cache invalidated) and must be escaped, not raw-injected.
        $public = $this->get(route('home'));
        $public->assertOk();
        $public->assertDontSee('<script>alert(1)</script>', false);
        $public->assertSee(e("O'Brien's <script>alert(1)</script> Academy"), false);
    }

    public function test_update_invalidates_the_cache_immediately(): void
    {
        /** @var SiteContentRepositoryInterface $repo */
        $repo = app(SiteContentRepositoryInterface::class);

        // Warm the cache with the original value.
        $this->assertSame(SiteContent::DEFAULTS['hero.cta_label'], $repo->get('hero.cta_label'));

        $payload = $this->validPayload();
        $payload['content']['hero']['cta_label'] = 'Join Now';

        $this->actingAs($this->admin())->patch(route('admin.site-content.update'), $payload);

        $this->assertSame('Join Now', $repo->get('hero.cta_label'));
    }

    // ─── Site name/short-label propagation (site.name / site.short_label) ──

    private function mockGoogleUser(string $id, string $email, string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);
        $socialiteUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_renaming_site_propagates_to_public_landing_page_title_and_navbar(): void
    {
        $payload = $this->validPayload();
        $payload['content']['site']['name']        = 'Acme Academy';
        $payload['content']['site']['short_label']  = 'AA';

        $this->actingAs($this->admin())->patch(route('admin.site-content.update'), $payload);
        $this->post(route('logout'));

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>Acme Academy — Private Learning Platform</title>', false);
        $response->assertSee('AA');
        // Deliberately NOT asserting the whole page is free of "EduNest" — hero.subheading,
        // features.eyebrow, and footer.copyright are separate free-text admin-editable
        // fields (see main task) that happen to default to prose mentioning "EduNest";
        // they are intentionally independent of site.name, not auto-interpolated.
    }

    public function test_renaming_site_propagates_to_authenticated_admin_layout(): void
    {
        $payload = $this->validPayload();
        $payload['content']['site']['name']        = 'Acme Academy';
        $payload['content']['site']['short_label']  = 'AA';
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.site-content.update'), $payload);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Acme Academy Admin', false);
        $response->assertSee("happening on Acme Academy", false);
        $response->assertDontSee('EduNest');
    }

    public function test_welcome_email_uses_the_current_site_name_not_the_default(): void
    {
        Mail::fake();
        $this->studentRoleForGoogle();

        $payload = $this->validPayload();
        $payload['content']['site']['name'] = 'Acme Academy';
        $this->actingAs($this->admin())->patch(route('admin.site-content.update'), $payload);
        $this->post(route('logout'));

        $this->mockGoogleUser('google-acme-1', 'acme-signup@example.com', 'New Student');
        $this->get(route('auth.google.callback'));

        Mail::assertQueued(WelcomeEmail::class, function ($mail) {
            $rendered = $mail->render();
            return str_contains($mail->envelope()->subject, 'Acme Academy')
                && str_contains($rendered, 'Welcome to Acme Academy')
                && ! str_contains($rendered, 'EduNest');
        });
    }

    public function test_reverting_site_name_restores_the_original_everywhere(): void
    {
        $admin = $this->admin();
        $payload = $this->validPayload();
        $payload['content']['site']['name']       = 'Acme Academy';
        $payload['content']['site']['short_label'] = 'AA';
        $this->actingAs($admin)->patch(route('admin.site-content.update'), $payload);

        // Revert.
        $revert = $this->validPayload(); // already back to EduNest/EN defaults
        $this->actingAs($admin)->patch(route('admin.site-content.update'), $revert);
        $this->post(route('logout'));

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>EduNest — Private Learning Platform</title>', false);
        $response->assertDontSee('Acme Academy');
    }

    private function studentRoleForGoogle(): void
    {
        Role::firstOrCreate(['name' => 'student']);
    }

    // ─── Validation ─────────────────────────────────────────────────────

    public function test_update_rejects_a_field_exceeding_its_max_length(): void
    {
        $payload = $this->validPayload();
        $payload['content']['hero']['cta_label'] = str_repeat('x', 31); // max is 30

        $response = $this->actingAs($this->admin())
            ->patch(route('admin.site-content.update'), $payload);

        $response->assertSessionHasErrors('content.hero.cta_label');
        $this->assertSame(
            SiteContent::DEFAULTS['hero.cta_label'],
            SiteContent::where('key', 'hero.cta_label')->value('value')
        );
    }

    public function test_update_rejects_a_missing_required_field(): void
    {
        $payload = $this->validPayload();
        unset($payload['content']['footer']['copyright']);

        $response = $this->actingAs($this->admin())
            ->patch(route('admin.site-content.update'), $payload);

        $response->assertSessionHasErrors('content.footer.copyright');
    }

    /** A full, valid nested payload matching UpdateSiteContentRequest's shape exactly. */
    private function validPayload(): array
    {
        return [
            'content' => [
                'site' => ['name' => 'EduNest', 'short_label' => 'EN'],
                'nav' => ['sign_in_label' => 'Sign in'],
                'hero' => [
                    'badge'         => 'Private · Instructor-Led · Focused',
                    'heading_line1' => 'A focused space to',
                    'heading_line2' => 'learn, not wander.',
                    'subheading'    => 'EduNest connects students directly with their courses.',
                    'cta_label'     => 'Get Started',
                    'caption'       => 'First login creates your student account automatically.',
                ],
                'features' => [
                    'eyebrow' => 'What EduNest Offers',
                    'heading' => 'Built for real learning',
                ],
                'feature' => [
                    '1' => ['title' => 'Student-Centered Learning', 'description' => 'Access your courses.'],
                    '2' => ['title' => 'Teacher-Led Success', 'description' => 'Instructors build courses.'],
                    '3' => ['title' => 'Private & Secure', 'description' => 'No public marketplace.'],
                ],
                'how_it_works' => [
                    'eyebrow' => 'Simple by Design',
                    'heading' => 'How It Works',
                    '1' => ['title' => 'Join', 'description' => 'Sign in with Google.'],
                    '2' => ['title' => 'Learn', 'description' => 'Enter your token.'],
                    '3' => ['title' => 'Succeed', 'description' => 'Progress at your pace.'],
                ],
                'footer' => [
                    'link' => ['privacy' => 'Privacy', 'terms' => 'Terms', 'support' => 'Support'],
                    'copyright' => 'EduNest. All rights reserved.',
                ],
            ],
        ];
    }
}
