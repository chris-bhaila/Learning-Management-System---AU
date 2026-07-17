<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\Token;
use App\Models\User;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Covers the hard-delete → soft-revoke reversal: Token::revoked_at, the revoke
 * repository/controller path, the removed manual Delete action, the widened
 * tokens:prune window, and the notify-expired job's revoked_at skip guard.
 */
class TokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
    }

    private function student(): User
    {
        return User::factory()->student()->create();
    }

    private function repo(): TokenRepositoryInterface
    {
        return app(TokenRepositoryInterface::class);
    }

    // ─── Revoke sets revoked_at, does not delete ───────────────────────

    public function test_teacher_revoking_a_token_sets_revoked_at_and_does_not_delete_the_row(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($teacher)->patch(route('teacher.tokens.revoke', $token->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Token revoked.');

        $this->assertDatabaseHas('tokens', ['id' => $token->id]);
        $fresh = $token->fresh();
        $this->assertNotNull($fresh->revoked_at);
        $this->assertTrue($fresh->isRevoked());
    }

    public function test_admin_revoking_a_token_sets_revoked_at_and_does_not_delete_the_row(): void
    {
        $admin   = $this->admin();
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($admin)->patch(route('admin.tokens.revoke', $token->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('tokens', ['id' => $token->id]);
        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_teacher_cannot_revoke_another_teachers_token(): void
    {
        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $token    = Token::factory()->for($teacherB, 'teacher')->create();

        $response = $this->actingAs($teacherA)->patch(route('teacher.tokens.revoke', $token->id));

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertNull($token->fresh()->revoked_at);
    }

    // ─── Revoked token fails enrollment ─────────────────────────────────

    public function test_revoked_class_token_is_rejected_at_enrollment(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = Token::factory()->for($teacher, 'teacher')->create();
        $this->repo()->revoke($token);

        $response = $this->actingAs($student)
            ->post(route('student.enroll'), ['token_value' => $token->token_value]);

        $response->assertSessionHasErrors('token_value');
        $this->assertDatabaseMissing('teacher_student', ['teacher_id' => $teacher->id, 'student_id' => $student->id]);
    }

    public function test_revoked_token_isexpired_is_true_regardless_of_expires_at_or_uses(): void
    {
        $teacher = $this->teacher();
        // Plenty of time and uses left — only revoked_at should make this invalid.
        $token = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->addDays(10),
            'max_uses'   => 100,
            'uses_count' => 0,
        ]);

        $this->assertFalse($token->isExpired());

        $this->repo()->revoke($token);

        $this->assertTrue($token->fresh()->isExpired());
    }

    // ─── Revoke notification: logs correctly, distinct from expiry ─────

    public function test_revoking_a_class_token_logs_a_distinct_revoked_notification(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $this->repo()->revoke($token);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Class token revoked',
        ]);
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Class token expired: time limit reached',
        ]);
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Class token expired: max uses reached',
        ]);

        $logged = Activity::where('description', 'Class token revoked')->first();
        $this->assertSame($token->token_value, $logged->properties['token_value']);
        $this->assertSame($teacher->id, $logged->properties['teacher_id']);
    }

    public function test_revoking_a_course_token_logs_course_specific_revoked_notification(): void
    {
        $teacher = $this->teacher();
        $course  = Course::factory()->for($teacher, 'teacher')->create(['title' => 'Biology 101']);
        $token   = Token::factory()->forCourse($course)->create(['teacher_id' => $teacher->id]);

        $this->repo()->revoke($token);

        $logged = Activity::where('description', 'Course token revoked')->first();
        $this->assertNotNull($logged);
        $this->assertSame($course->id, $logged->properties['course_id']);
        $this->assertSame('Biology 101', $logged->properties['course_title']);
    }

    public function test_revoked_notification_appears_on_teacher_activity_feed_distinct_from_expiry(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();
        $this->repo()->revoke($token);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertSee('has been revoked');
        $response->assertDontSee('Time limit reached!');
        $response->assertDontSee('Max uses');
    }

    // ─── notify-expired job skips revoked tokens ───────────────────────

    public function test_notify_expired_job_does_not_fire_for_an_already_revoked_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->addMinutes(5),
        ]);
        $this->repo()->revoke($token);

        // Time now passes the token's original expiry.
        $token->forceFill(['expires_at' => now()->subMinutes(5)])->save();

        Artisan::call('tokens:notify-expired');

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Class token expired: time limit reached',
        ]);
        // Only the original revoke notification exists, nothing new was added.
        $this->assertSame(1, Activity::where('properties->token_value', $token->token_value)->count());
    }

    public function test_notify_expired_job_still_fires_for_a_genuinely_time_expired_non_revoked_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->subMinutes(5),
        ]);

        Artisan::call('tokens:notify-expired');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Class token expired: time limit reached',
        ]);
    }

    // ─── Manual Delete action no longer exists ─────────────────────────

    public function test_delete_route_no_longer_exists(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.tokens.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('teacher.tokens.destroy'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.tokens.revoke'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('teacher.tokens.revoke'));
    }

    public function test_delete_verb_on_token_url_is_rejected(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($teacher)->delete("/teacher/tokens/{$token->id}");

        $response->assertNotFound(); // route no longer registered for DELETE
        $this->assertDatabaseHas('tokens', ['id' => $token->id, 'revoked_at' => null]);
    }

    public function test_no_action_button_shown_for_an_already_revoked_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();
        $this->repo()->revoke($token);

        $response = $this->actingAs($teacher)->get(route('teacher.tokens.class'));

        $response->assertOk();
        // The standalone hidden form still exists (harmless, pre-existing structure —
        // unconditionally rendered per token) — what must be absent is the visible
        // trigger button/confirmation that would let a teacher submit it.
        $response->assertDontSee('Revoke this class token?');
        $response->assertDontSee('confirmDestructive', false);
    }

    public function test_no_action_button_shown_for_a_naturally_expired_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->expired()->create();

        $response = $this->actingAs($teacher)->get(route('teacher.tokens.class'));

        $response->assertOk();
        $response->assertDontSee('confirmDestructive', false);
    }

    public function test_action_button_shown_for_a_still_active_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($teacher)->get(route('teacher.tokens.class'));

        $response->assertOk();
        $response->assertSee('id="delete-token-form-' . $token->id . '"', false);
        $response->assertSee('Revoke this class token?');
    }

    // ─── tokens:prune widened window ────────────────────────────────────

    public function test_prune_does_not_delete_a_token_expired_only_10_days_ago(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->subDays(10),
        ]);

        Artisan::call('tokens:prune');

        $this->assertDatabaseHas('tokens', ['id' => $token->id]);
    }

    public function test_prune_deletes_a_token_expired_200_days_ago(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->subDays(200),
        ]);

        Artisan::call('tokens:prune');

        $this->assertDatabaseMissing('tokens', ['id' => $token->id]);
    }

    public function test_prune_does_not_delete_a_token_expired_exactly_100_days_ago_old_threshold_would_have(): void
    {
        // Would have been deleted under the OLD 7-day threshold — confirms the window
        // actually widened, not just that the constant changed without effect.
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create([
            'expires_at' => now()->subDays(100),
        ]);

        Artisan::call('tokens:prune');

        $this->assertDatabaseHas('tokens', ['id' => $token->id]);
    }

    // ─── Usage history page — three states ─────────────────────────────

    public function test_usage_page_shows_revoked_state_distinct_from_live_and_pruned(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();
        $this->repo()->revoke($token);

        $response = $this->actingAs($teacher)->get(route('teacher.tokens.usage', $token->token_value));

        $response->assertOk();
        $response->assertSee('Token Revoked');
        $response->assertDontSee('Live Token Stats');
        $response->assertDontSee('been removed from active tokens');
    }

    public function test_usage_page_shows_live_state_for_an_active_token(): void
    {
        $teacher = $this->teacher();
        $token   = Token::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($teacher)->get(route('teacher.tokens.usage', $token->token_value));

        $response->assertOk();
        $response->assertSee('Live Token Stats');
        $response->assertDontSee('Token Revoked');
    }
}
