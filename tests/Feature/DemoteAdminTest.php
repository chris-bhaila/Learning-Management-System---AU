<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class DemoteAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'teacher', 'student'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::where('name', 'super_admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id'   => Role::where('name', 'admin')->first()->id,
            'is_active' => true,
        ], $overrides));
    }

    private function demote(User $actor, User $target, string $role)
    {
        return $this->actingAs($actor)
            ->patch(route('admin.users.demoteAdmin', $target->id), ['role' => $role]);
    }

    /** @test */
    public function test_super_admin_can_demote_admin_to_teacher(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin', 'email' => 'target1@edunest.dev']);

        $response = $this->demote($superAdmin, $target, 'teacher');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('teacher', $target->role->name);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Demoted admin role to user',
        ]);
    }

    /** @test */
    public function test_super_admin_can_demote_admin_to_student(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 2', 'email' => 'target2@edunest.dev']);

        $response = $this->demote($superAdmin, $target, 'student');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('student', $target->role->name);
    }

    /** @test */
    public function test_regular_admin_cannot_demote_another_admin(): void
    {
        $actingAdmin = $this->admin(['email' => 'acting@edunest.dev']);
        $target      = $this->admin(['name' => 'Target Admin 3', 'email' => 'target3@edunest.dev']);

        $response = $this->demote($actingAdmin, $target, 'teacher');

        // AuthorizationException is redirected to a flash error in this app (see
        // bootstrap/app.php), not a raw 403 — same convention used by promoteToAdmin's
        // sibling tests (CreateAdminAccountTest).
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $target->refresh();
        $this->assertSame('admin', $target->role->name);
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Demoted admin role to user',
        ]);
    }

    /** @test */
    public function test_super_admin_cannot_demote_a_super_admin(): void
    {
        $superAdmin       = $this->superAdmin();
        $otherSuperAdmin  = User::factory()->create([
            'role_id'   => Role::where('name', 'super_admin')->first()->id,
            'is_active' => true,
            'email'     => 'other-superadmin@edunest.dev',
        ]);

        $response = $this->demote($superAdmin, $otherSuperAdmin, 'teacher');

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $otherSuperAdmin->refresh();
        $this->assertSame('super_admin', $otherSuperAdmin->role->name);
    }

    /** @test */
    public function test_invalid_target_role_admin_is_rejected(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 4', 'email' => 'target4@edunest.dev']);

        $response = $this->demote($superAdmin, $target, 'admin');

        $response->assertSessionHasErrors('role');

        $target->refresh();
        $this->assertSame('admin', $target->role->name);
    }

    /** @test */
    public function test_invalid_target_role_super_admin_is_rejected(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 5', 'email' => 'target5@edunest.dev']);

        $response = $this->demote($superAdmin, $target, 'super_admin');

        $response->assertSessionHasErrors('role');

        $target->refresh();
        $this->assertSame('admin', $target->role->name);
    }

    /** @test */
    public function test_invalid_target_role_garbage_string_is_rejected(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 6', 'email' => 'target6@edunest.dev']);

        $response = $this->demote($superAdmin, $target, 'not-a-role');

        $response->assertSessionHasErrors('role');

        $target->refresh();
        $this->assertSame('admin', $target->role->name);
    }

    /** @test */
    public function test_demoted_admin_immediately_loses_access_to_admin_routes(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 7', 'email' => 'target7@edunest.dev']);

        $this->demote($superAdmin, $target, 'teacher');
        $target->refresh();

        $response = $this->actingAs($target)->get(route('admin.users.index'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_demoted_admin_is_no_longer_excluded_from_google_oauth(): void
    {
        $superAdmin = $this->superAdmin();
        $target     = $this->admin(['name' => 'Target Admin 8', 'email' => 'target8@edunest.dev']);

        $this->demote($superAdmin, $target, 'teacher');
        $target->refresh();

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-target8');
        $socialiteUser->shouldReceive('getEmail')->andReturn('target8@edunest.dev');
        $socialiteUser->shouldReceive('getName')->andReturn('Target Admin 8');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');
        $socialiteUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($target->fresh());
    }
}
