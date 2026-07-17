<?php

namespace Tests\Feature;

use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the "All Activity" page (Teacher\ActivityController) — the same allowlisted
 * feed as the dashboard card, but paginated via cursor ("Load more"), not numbered
 * pagination. See App\Helpers\TeacherActivityHelper for the shared query.
 */
class TeacherActivityPageTest extends TestCase
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

    private function logJoin(User $teacher, User $student, string $tokenValue = 'TOK1'): void
    {
        activity()
            ->causedBy($student)
            ->withProperties([
                'token_value'  => $tokenValue,
                'token_type'   => 'class',
                'teacher_id'   => $teacher->id,
                'teacher_name' => $teacher->name,
            ])
            ->log('Student joined teacher class via class token');
    }

    private function logCourseJoin(User $teacher, User $student, string $tokenValue, string $courseTitle): void
    {
        activity()
            ->causedBy($student)
            ->withProperties([
                'token_value'  => $tokenValue,
                'token_type'   => 'course',
                'teacher_id'   => $teacher->id,
                'teacher_name' => $teacher->name,
                'course_id'    => 1,
                'course_title' => $courseTitle,
            ])
            ->log('Student enrolled in course via course token');
    }

    private function logExpiry(User $teacher, string $description, string $tokenValue): void
    {
        activity()
            ->withProperties([
                'token_value' => $tokenValue,
                'teacher_id'  => $teacher->id,
                'max_uses'    => 1,
            ])
            ->log($description);
    }

    public function test_page_renders_first_batch_of_twenty_and_a_load_more_button(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();

        for ($i = 0; $i < 25; $i++) {
            $this->logJoin($teacher, $student, "TOK{$i}");
        }

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index'));

        $response->assertOk();
        $response->assertSee('All Activity');
        $html = $response->getContent();
        $this->assertSame(20, substr_count($html, 'joined your class'));
        $this->assertStringContainsString('data-next-url=', $html);
        $this->assertStringNotContainsString('data-next-url=""', $html);
    }

    public function test_load_more_ajax_endpoint_returns_the_remaining_items_and_no_further_marker(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();

        for ($i = 0; $i < 25; $i++) {
            $this->logJoin($teacher, $student, "TOK{$i}");
        }

        $first = $this->actingAs($teacher)->get(route('teacher.activity.index'));
        preg_match('/data-next-url="([^"]*)"/', $first->getContent(), $matches);
        $nextUrl = html_entity_decode($matches[1]);

        $second = $this->actingAs($teacher)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($nextUrl);

        $second->assertOk();
        $html = $second->getContent();
        $this->assertSame(5, substr_count($html, 'joined your class'));
        $this->assertStringNotContainsString('data-next-page-url', $html);
        // The AJAX partial is just the list fragment — not a full HTML document.
        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_page_shows_no_load_more_button_when_everything_fits_on_one_page(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->logJoin($teacher, $student);

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('data-next-url=""', $html);
        $this->assertStringContainsString('display:none', $html);
    }

    public function test_page_uses_the_same_allowlist_as_the_dashboard_card(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->logJoin($teacher, $student);

        activity()
            ->causedBy($student)
            ->withProperties(['teacher_id' => $teacher->id])
            ->log('Some unrelated future event type');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index'));

        $response->assertOk();
        $response->assertSee('joined your class');
        $response->assertDontSee('Some unrelated future event type');
    }

    public function test_teacher_cannot_see_another_teachers_activity_on_this_page(): void
    {
        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $studentA = $this->student();

        $this->logJoin($teacherA, $studentA, 'TOKA1');
        $this->logJoin($teacherB, $this->student(), 'TOKB1');

        $response = $this->actingAs($teacherA)->get(route('teacher.activity.index'));

        $response->assertOk();
        $response->assertSee($studentA->name);
        $response->assertDontSee('TOKB1');
    }

    public function test_empty_state_when_teacher_has_no_activity(): void
    {
        $teacher = $this->teacher();

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index'));

        $response->assertOk();
        $response->assertSee('No activity yet.');
    }

    public function test_guest_and_other_roles_cannot_access_the_page(): void
    {
        $guestResponse = $this->get(route('teacher.activity.index'));
        $guestResponse->assertRedirect(route('login'));

        $student = $this->student();
        $studentResponse = $this->actingAs($student)->get(route('teacher.activity.index'));
        $studentResponse->assertRedirect(route('student.dashboard'));
    }

    // ─── Search ─────────────────────────────────────────────────────────

    public function test_search_matches_student_name(): void
    {
        $teacher = $this->teacher();
        $alex = User::factory()->student()->create(['name' => 'Alex Rivera']);
        $sam  = User::factory()->student()->create(['name' => 'Sam Chen']);
        $this->logJoin($teacher, $alex, 'TOK-ALEX');
        $this->logJoin($teacher, $sam, 'TOK-SAM');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['search' => 'Alex']));

        $response->assertOk();
        $response->assertSee('Alex Rivera');
        $response->assertDontSee('Sam Chen');
    }

    public function test_search_matches_token_value(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->logJoin($teacher, $student, 'FINDME123');
        $this->logCourseJoin($teacher, $student, 'OTHERTOK', 'Biology');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['search' => 'FINDME']));

        $response->assertOk();
        $response->assertSee('joined your class');
        $response->assertDontSee('OTHERTOK');
    }

    public function test_search_matches_course_title(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->logCourseJoin($teacher, $student, 'CRSTOK1', 'Advanced Chemistry');
        $this->logJoin($teacher, $student, 'CLSTOK1');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['search' => 'Chemistry']));

        $response->assertOk();
        $response->assertSee('Advanced Chemistry');
        $response->assertDontSee('CLSTOK1');
    }

    public function test_search_with_no_matches_shows_empty_state(): void
    {
        $teacher = $this->teacher();
        $this->logJoin($teacher, $this->student());

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['search' => 'NoSuchThing']));

        $response->assertOk();
        $response->assertSee('No activity yet.');
    }

    // ─── Type filter ────────────────────────────────────────────────────

    public function test_type_filter_joined_class(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->logJoin($teacher, $student, 'CLSTOK1');
        $this->logCourseJoin($teacher, $student, 'CRSTOK1', 'Biology');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['type' => 'joined_class']));

        $response->assertOk();
        $response->assertSee('joined your class');
        $response->assertDontSee('Biology');
    }

    public function test_type_filter_expired_max_uses_groups_both_class_and_course(): void
    {
        $teacher = $this->teacher();
        $this->logExpiry($teacher, 'Class token expired: max uses reached', 'CLSTOK1');
        $this->logExpiry($teacher, 'Course token expired: time limit reached', 'CRSTOK1');

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['type' => 'expired_max_uses']));

        $response->assertOk();
        $response->assertSee('CLSTOK1');
        $response->assertDontSee('CRSTOK1');
    }

    public function test_unknown_type_value_is_ignored_not_erroring(): void
    {
        $teacher = $this->teacher();
        $this->logJoin($teacher, $this->student());

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', ['type' => 'totally-bogus']));

        $response->assertOk();
        $response->assertSee('joined your class');
    }

    public function test_search_and_type_filter_combine(): void
    {
        $teacher = $this->teacher();
        $alex = User::factory()->student()->create(['name' => 'Alex Rivera']);
        $this->logJoin($teacher, $alex, 'CLSTOK1');
        $this->logCourseJoin($teacher, $alex, 'CRSTOK1', 'Biology'); // same student, different type

        $response = $this->actingAs($teacher)->get(route('teacher.activity.index', [
            'search' => 'Alex', 'type' => 'joined_course',
        ]));

        $response->assertOk();
        $response->assertSee('Biology');
        $response->assertDontSee('CLSTOK1');
    }

    public function test_filters_are_scoped_to_the_teachers_own_activity(): void
    {
        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $sameNameStudentA = User::factory()->student()->create(['name' => 'Casey Kim']);
        $sameNameStudentB = User::factory()->student()->create(['name' => 'Casey Kim']);
        $this->logJoin($teacherA, $sameNameStudentA, 'TOKA1');
        $this->logJoin($teacherB, $sameNameStudentB, 'TOKB1');

        $response = $this->actingAs($teacherA)->get(route('teacher.activity.index', ['search' => 'Casey']));

        $response->assertOk();
        // The class-join message doesn't render the token value, so both teachers'
        // entries would read identically ("Casey Kim joined your class") if scoping
        // failed — the real check is that exactly ONE such row renders, not zero/two.
        $this->assertSame(1, substr_count($response->getContent(), 'joined your class'));
    }

    public function test_load_more_preserves_active_filters(): void
    {
        $teacher = $this->teacher();
        $alex = User::factory()->student()->create(['name' => 'Alex Rivera']);
        $sam  = User::factory()->student()->create(['name' => 'Sam Chen']);

        for ($i = 0; $i < 25; $i++) {
            $this->logJoin($teacher, $alex, "ALEXTOK{$i}");
        }
        $this->logJoin($teacher, $sam, 'SAMTOK1');

        $first = $this->actingAs($teacher)->get(route('teacher.activity.index', ['search' => 'Alex']));
        $first->assertOk();
        preg_match('/data-next-url="([^"]*)"/', $first->getContent(), $matches);
        $nextUrl = html_entity_decode($matches[1]);

        $this->assertStringContainsString('search=Alex', $nextUrl);

        $second = $this->actingAs($teacher)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($nextUrl);

        $second->assertOk();
        $second->assertDontSee('SAMTOK1');
    }
}
