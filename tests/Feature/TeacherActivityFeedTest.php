<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Covers the teacher dashboard's "Recent Activity" feed: the allowlisted query
 * in Teacher\DashboardController::index(), the max-uses expiry trigger in
 * EloquentTokenRepository::incrementUses(), the time-limit expiry trigger in
 * the tokens:notify-expired command, and resources/views/teacher/dashboard.blade.php's
 * per-case bold-interpolated rendering.
 */
class TeacherActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->student()->create();
    }

    private function course(User $teacher): Course
    {
        return Course::factory()->for($teacher, 'teacher')->create();
    }

    private function classToken(User $teacher, array $overrides = []): Token
    {
        return Token::factory()->for($teacher, 'teacher')->create($overrides);
    }

    private function courseToken(User $teacher, Course $course, array $overrides = []): Token
    {
        return Token::factory()->forCourse($course)->create($overrides);
    }

    private function enroll(User $student, string $tokenValue)
    {
        return $this->actingAs($student)
            ->post(route('student.enroll'), ['token_value' => $tokenValue]);
    }

    private function dashboard(User $teacher)
    {
        return $this->actingAs($teacher)->get(route('teacher.dashboard'));
    }

    // ─── (a) Student joined class via class token ──────────────────────

    public function test_case_a_student_joined_class_via_class_token(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher);

        $this->enroll($student, $token->token_value)->assertSessionHasNoErrors();

        $response = $this->dashboard($teacher);

        $response->assertOk();
        // Student name is a link to their teacher-scoped profile page, not plain bold text.
        $response->assertSee($student->name);
        $response->assertSee('href="' . route('teacher.students.show', $student->id) . '"', false);
        $response->assertSee('joined your class');
    }

    public function test_student_name_link_falls_back_to_plain_text_if_causer_was_deleted(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher);

        $this->enroll($student, $token->token_value)->assertSessionHasNoErrors();
        $student->forceDelete();

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee('<strong>A student</strong>', false);
        $response->assertSee('joined your class');
    }

    // ─── (b) Student joined course via course token ────────────────────

    public function test_case_b_student_joined_course_via_course_token(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $classToken  = $this->classToken($teacher);
        $courseToken = $this->courseToken($teacher, $course);

        $this->enroll($student, $classToken->token_value)->assertSessionHasNoErrors();
        $this->enroll($student, $courseToken->token_value)->assertSessionHasNoErrors();

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee('href="' . route('teacher.students.show', $student->id) . '"', false);
        $response->assertSee("<strong>{$course->title}</strong>", false);
        $response->assertSee("<strong>{$courseToken->token_value}</strong>", false);
        $response->assertSee('joined your course');
        $response->assertSee('via token');
    }

    // ─── (c) Course token expired — max uses reached ───────────────────

    public function test_case_c_course_token_max_uses_reached(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $classToken  = $this->classToken($teacher);
        $courseToken = $this->courseToken($teacher, $course, ['max_uses' => 1, 'uses_count' => 0]);

        $this->enroll($student, $classToken->token_value)->assertSessionHasNoErrors();
        $this->enroll($student, $courseToken->token_value)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tokens', [
            'id' => $courseToken->id, 'uses_count' => 1, 'expiry_notified' => true,
        ]);

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee("<strong>{$courseToken->token_value}</strong>", false);
        $response->assertSee("<strong>{$course->title}</strong>", false);
        $response->assertSee('<strong>1</strong>', false);
        $response->assertSee('has expired');
        $response->assertSee('Max uses');
        $response->assertSee('reached!');
    }

    // ─── (d) Class token expired — max uses reached ────────────────────

    public function test_case_d_class_token_max_uses_reached(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $classToken = $this->classToken($teacher, ['max_uses' => 1, 'uses_count' => 0]);

        $this->enroll($student, $classToken->token_value)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tokens', [
            'id' => $classToken->id, 'uses_count' => 1, 'expiry_notified' => true,
        ]);

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee("<strong>{$classToken->token_value}</strong>", false);
        $response->assertSee('<strong>1</strong>', false);
        $response->assertSee('for your class');
        $response->assertSee('has expired');
        $response->assertSee('Max uses');
        $response->assertSee('reached!');
    }

    // ─── (e) Course token expired — time limit reached ─────────────────

    public function test_case_e_course_token_time_limit_reached(): void
    {
        $teacher = $this->teacher();
        $course  = $this->course($teacher);
        $courseToken = $this->courseToken($teacher, $course, ['expires_at' => now()->subMinutes(12)]);

        Artisan::call('tokens:notify-expired');

        $this->assertDatabaseHas('tokens', ['id' => $courseToken->id, 'expiry_notified' => true]);

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee("<strong>{$courseToken->token_value}</strong>", false);
        $response->assertSee("<strong>{$course->title}</strong>", false);
        $response->assertSee('has expired');
        $response->assertSee('Time limit reached!');
    }

    // ─── (f) Class token expired — time limit reached ──────────────────

    public function test_case_f_class_token_time_limit_reached(): void
    {
        $teacher = $this->teacher();
        $classToken = $this->classToken($teacher, ['expires_at' => now()->subMinutes(12)]);

        Artisan::call('tokens:notify-expired');

        $this->assertDatabaseHas('tokens', ['id' => $classToken->id, 'expiry_notified' => true]);

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertSee("<strong>{$classToken->token_value}</strong>", false);
        $response->assertSee('for your class');
        $response->assertSee('has expired');
        $response->assertSee('Time limit reached!');
    }

    // ─── Scheduled command never double-fires for the same token ───────

    public function test_notify_expired_command_does_not_double_fire_across_runs(): void
    {
        $teacher = $this->teacher();
        $this->classToken($teacher, ['expires_at' => now()->subMinutes(12)]);

        Artisan::call('tokens:notify-expired');
        Artisan::call('tokens:notify-expired');
        Artisan::call('tokens:notify-expired');

        $this->assertSame(
            1,
            \Spatie\Activitylog\Models\Activity::where('description', 'Class token expired: time limit reached')->count()
        );
    }

    public function test_incrementuses_does_not_double_fire_for_the_same_token(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $classToken = $this->classToken($teacher, ['max_uses' => 1, 'uses_count' => 0]);

        $this->enroll($student, $classToken->token_value);

        // Token is now maxed and expiry_notified — confirm a second attempt (which the
        // enrollment flow itself would reject as "expired" before ever reaching
        // incrementUses again) doesn't somehow produce a second notification either.
        \App\Repositories\Contracts\TokenRepositoryInterface::class;
        $repo = app(\App\Repositories\Contracts\TokenRepositoryInterface::class);
        $repo->incrementUses($classToken->fresh());

        $this->assertSame(
            1,
            \Spatie\Activitylog\Models\Activity::where('description', 'Class token expired: max uses reached')->count()
        );
    }

    // ─── Teacher isolation ──────────────────────────────────────────────

    public function test_teacher_only_sees_their_own_students_and_tokens_not_another_teachers(): void
    {
        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $studentA = $this->student();

        $tokenA = $this->classToken($teacherA);
        $this->enroll($studentA, $tokenA->token_value)->assertSessionHasNoErrors();

        $classTokenB = $this->classToken($teacherB, ['expires_at' => now()->subMinutes(5)]);
        Artisan::call('tokens:notify-expired');

        $responseA = $this->dashboard($teacherA);
        $responseA->assertOk();
        $responseA->assertSee("joined your class", false);
        $responseA->assertDontSee($classTokenB->token_value, false);

        $responseB = $this->dashboard($teacherB);
        $responseB->assertOk();
        $responseB->assertDontSee($studentA->name);
        $responseB->assertSee($classTokenB->token_value, false);
    }

    // ─── Allowlist excludes non-listed activity, even from the teacher's own students ──

    public function test_non_allowlisted_activity_from_own_students_does_not_appear(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher);
        $this->enroll($student, $token->token_value);

        // A generic, non-allowlisted activity caused by this same student, scoped to
        // this same teacher_id in properties — must NOT render, proving the filter is
        // description-based, not just teacher_id-based.
        activity()
            ->causedBy($student)
            ->withProperties(['teacher_id' => $teacher->id])
            ->log('Some unrelated future event type');

        $response = $this->dashboard($teacher);

        $response->assertOk();
        $response->assertDontSee('Some unrelated future event type');
    }

    // ─── Admin's activity log page is completely unaffected ────────────

    /**
     * Note: admin/logs/index.blade.php never renders Activity::$description as raw text
     * for ANY manual activity()->log() call (this app's join/enrollment-failure logs
     * included) — it only shows an event-config label (line 190-212) and the causer's
     * name (line 236-254); $description only appears in CSV export (see
     * ActivityLogController::export():69). So the real regression check is: (1) the page
     * still 200s and lists these rows at all (not silently excluded), via the causer name
     * and the "Total Events" count, and (2) the description text is intact in the export,
     * which is the one place it's actually surfaced to Admin.
     */
    public function test_admin_activity_log_page_and_export_are_unaffected(): void
    {
        $admin = User::factory()->create([
            'role_id'   => \App\Models\Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher, ['max_uses' => 1, 'uses_count' => 0]);
        $this->enroll($student, $token->token_value);

        // Both new rows genuinely exist (not filtered/excluded by anything this task adds).
        $this->assertDatabaseHas('activity_log', ['description' => 'Student joined teacher class via class token']);
        $this->assertDatabaseHas('activity_log', ['description' => 'Class token expired: max uses reached']);

        $response = $this->actingAs($admin)->get(route('admin.logs.index'));
        $response->assertOk();
        $response->assertSee($student->name); // causer of the join event, rendered directly

        // StreamedResponse content isn't captured by getContent()/assertSee() — Laravel's
        // TestResponse::streamedContent() actually invokes the streaming callback.
        $export = $this->actingAs($admin)->get(route('admin.logs.export'));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('Student joined teacher class via class token', $csv);
        $this->assertStringContainsString('Class token expired: max uses reached', $csv);
    }
}
