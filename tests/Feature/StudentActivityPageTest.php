<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the student "All Activity" page (Student\ActivityController) — the same
 * allowlisted feed as the dashboard card, but paginated via cursor ("Load more"), not
 * numbered pagination. Mirrors TeacherActivityPageTest exactly. See
 * App\Helpers\StudentActivityHelper for the shared query.
 */
class StudentActivityPageTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->teacher()->create(['name' => 'Ms. Rivera']);
    }

    private function student(): User
    {
        return User::factory()->student()->create(['name' => 'Alex']);
    }

    private function course(User $teacher, string $title = 'Biology 101'): Course
    {
        return Course::factory()->for($teacher, 'teacher')->create(['title' => $title]);
    }

    private function enrollActive(User $teacher, User $student, Course $course): void
    {
        $teacher->students()->attach($student->id, ['is_active' => true, 'enrolled_at' => now()]);
        $student->enrolledCourses()->attach($course->id, ['is_active' => true, 'enrolled_at' => now()]);
    }

    private function uploadToCourse(User $teacher, Course $course, string $filename = 'a.pdf'): void
    {
        app(FileRepositoryInterface::class)->storeUploads(
            [UploadedFile::fake()->create($filename, 10, 'application/pdf')],
            'App\Models\Course',
            $course->id,
            $teacher->id
        );
    }

    private function publishUnit(Course $course, string $title = 'Cell Biology'): void
    {
        app(UnitRepositoryInterface::class)->create([
            'course_id' => $course->id, 'title' => $title, 'content' => 'x', 'order' => 1,
        ]);
    }

    public function test_page_renders_first_batch_of_twenty_and_a_load_more_button(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        for ($i = 0; $i < 25; $i++) {
            $this->uploadToCourse($teacher, $course, "doc{$i}.pdf");
        }

        $response = $this->actingAs($student)->get(route('student.activity.index'));

        $response->assertOk();
        $response->assertSee('All Activity');
        $html = $response->getContent();
        $this->assertSame(20, substr_count($html, 'uploaded'));
        $this->assertStringContainsString('data-next-url=', $html);
        $this->assertStringNotContainsString('data-next-url=""', $html);
    }

    public function test_load_more_ajax_endpoint_returns_the_remaining_items_and_no_further_marker(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        for ($i = 0; $i < 25; $i++) {
            $this->uploadToCourse($teacher, $course, "doc{$i}.pdf");
        }

        $first = $this->actingAs($student)->get(route('student.activity.index'));
        preg_match('/data-next-url="([^"]*)"/', $first->getContent(), $matches);
        $nextUrl = html_entity_decode($matches[1]);

        $second = $this->actingAs($student)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($nextUrl);

        $second->assertOk();
        $html = $second->getContent();
        $this->assertSame(5, substr_count($html, 'uploaded'));
        $this->assertStringNotContainsString('data-next-page-url', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_page_shows_no_load_more_button_when_everything_fits_on_one_page(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);
        $this->uploadToCourse($teacher, $course);

        $response = $this->actingAs($student)->get(route('student.activity.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('data-next-url=""', $html);
        $this->assertStringContainsString('display:none', $html);
    }

    public function test_search_matches_teacher_name(): void
    {
        Storage::fake('private');
        $teacherA = User::factory()->teacher()->create(['name' => 'Ms. Rivera']);
        $teacherB = User::factory()->teacher()->create(['name' => 'Mr. Chen']);
        $student  = $this->student();
        $courseA  = $this->course($teacherA, 'Course A');
        $courseB  = $this->course($teacherB, 'Course B');
        $this->enrollActive($teacherA, $student, $courseA);
        $student->enrolledCourses()->attach($courseB->id, ['is_active' => true, 'enrolled_at' => now()]);
        $teacherB->students()->attach($student->id, ['is_active' => true, 'enrolled_at' => now()]);

        $this->uploadToCourse($teacherA, $courseA, 'rivera-file.pdf');
        $this->uploadToCourse($teacherB, $courseB, 'chen-file.pdf');

        $response = $this->actingAs($student)->get(route('student.activity.index', ['search' => 'Rivera']));

        $response->assertOk();
        $response->assertSee('rivera-file.pdf');
        $response->assertDontSee('chen-file.pdf');
    }

    public function test_search_matches_course_name(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher, 'Advanced Chemistry');
        $this->enrollActive($teacher, $student, $course);
        $this->uploadToCourse($teacher, $course, 'notes.pdf');

        $response = $this->actingAs($student)->get(route('student.activity.index', ['search' => 'Chemistry']));

        $response->assertOk();
        $response->assertSee('notes.pdf');
    }

    public function test_type_filter_unit_published(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $this->uploadToCourse($teacher, $course, 'a.pdf');
        $this->publishUnit($course, 'New Unit');

        $response = $this->actingAs($student)->get(route('student.activity.index', ['type' => 'unit_published']));

        $response->assertOk();
        $response->assertSee('New Unit');
        $response->assertDontSee('a.pdf');
    }

    public function test_unknown_type_value_is_ignored_not_erroring(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);
        $this->uploadToCourse($teacher, $course);

        $response = $this->actingAs($student)->get(route('student.activity.index', ['type' => 'totally-bogus']));

        $response->assertOk();
        $response->assertSee('uploaded');
    }

    public function test_student_only_sees_their_own_actively_enrolled_courses_activity(): void
    {
        Storage::fake('private');
        $teacher  = $this->teacher();
        $studentA = $this->student();
        $studentB = User::factory()->student()->create(['name' => 'Jordan']);
        $courseA  = $this->course($teacher, 'Course A');
        $courseB  = $this->course($teacher, 'Course B');
        $this->enrollActive($teacher, $studentA, $courseA);
        $this->enrollActive($teacher, $studentB, $courseB);

        $this->uploadToCourse($teacher, $courseB, 'only-b.pdf');

        $response = $this->actingAs($studentA)->get(route('student.activity.index'));

        $response->assertOk();
        $response->assertDontSee('only-b.pdf');
    }

    public function test_empty_state_when_student_has_no_activity(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->get(route('student.activity.index'));

        $response->assertOk();
        $response->assertSee('No activity yet.');
    }

    public function test_guest_and_other_roles_cannot_access_the_page(): void
    {
        $guestResponse = $this->get(route('student.activity.index'));
        $guestResponse->assertRedirect(route('login'));

        $teacher = $this->teacher();
        $teacherResponse = $this->actingAs($teacher)->get(route('student.activity.index'));
        $teacherResponse->assertRedirect(route('teacher.dashboard'));
    }

    public function test_load_more_preserves_active_filters(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        for ($i = 0; $i < 25; $i++) {
            $this->uploadToCourse($teacher, $course, "keep-{$i}.pdf");
        }
        $this->publishUnit($course, 'Should Be Filtered Out');

        $first = $this->actingAs($student)->get(route('student.activity.index', ['type' => 'file_course']));
        $first->assertOk();
        preg_match('/data-next-url="([^"]*)"/', $first->getContent(), $matches);
        $nextUrl = html_entity_decode($matches[1]);

        $this->assertStringContainsString('type=file_course', $nextUrl);

        $second = $this->actingAs($student)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($nextUrl);

        $second->assertOk();
        $second->assertDontSee('Should Be Filtered Out');
    }
}
