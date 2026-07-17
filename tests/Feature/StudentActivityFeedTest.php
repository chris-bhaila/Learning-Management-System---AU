<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\File;
use App\Models\Unit;
use App\Models\User;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the student-facing notification feed: the new logging call sites
 * (EloquentFileRepository::logUpload(), EloquentUnitRepository::logPublished()),
 * App\Helpers\StudentActivityHelper's allowlist/scoping, the dashboard rendering,
 * and the graceful degradation when a notified-about file is later deleted.
 */
class StudentActivityFeedTest extends TestCase
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

    private function unitRepo(): UnitRepositoryInterface
    {
        return app(UnitRepositoryInterface::class);
    }

    private function fileRepo(): FileRepositoryInterface
    {
        return app(FileRepositoryInterface::class);
    }

    private function dashboard(User $student)
    {
        return $this->actingAs($student)->get(route('student.dashboard'));
    }

    // ─── (a) File uploaded to a course ──────────────────────────────────

    public function test_case_a_file_uploaded_to_course(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('syllabus.pdf', 100, 'application/pdf')],
            'App\Models\Course',
            $course->id,
            $teacher->id
        );

        $response = $this->dashboard($student);

        $response->assertOk();
        $response->assertSee('<strong>Ms. Rivera</strong>', false);
        $response->assertSee('a PDF');
        $response->assertSee('syllabus.pdf');
        $response->assertSee('<strong>Biology 101</strong>', false);
    }

    // ─── (b) New unit published ─────────────────────────────────────────

    public function test_case_b_new_unit_published_at_creation(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $this->unitRepo()->create([
            'course_id' => $course->id,
            'title'     => 'Cell Biology',
            'content'   => 'intro',
            'order'     => 1,
        ]);

        $response = $this->dashboard($student);

        $response->assertOk();
        $response->assertSee('A new unit');
        $response->assertSee('<strong>Cell Biology</strong>', false);
        $response->assertSee('<strong>Biology 101</strong>', false);
        $response->assertSee('<strong>Ms. Rivera</strong>', false);
    }

    public function test_case_b_unit_republished_via_update_after_being_drafted(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $unit = $this->unitRepo()->create([
            'course_id'    => $course->id,
            'title'        => 'Draft Unit',
            'content'      => 'wip',
            'order'        => 1,
            'is_published' => false,
        ]);

        // Not published yet — no notification.
        $this->dashboard($student)->assertDontSee('Draft Unit');

        $this->unitRepo()->update($unit, ['is_published' => true]);

        $response = $this->dashboard($student);
        $response->assertOk();
        $response->assertSee('<strong>Draft Unit</strong>', false);
    }

    public function test_unpublishing_a_unit_does_not_log_a_publish_notification(): void
    {
        $teacher = $this->teacher();
        $course  = $this->course($teacher);

        $unit = $this->unitRepo()->create([
            'course_id' => $course->id, 'title' => 'Some Unit', 'content' => 'x', 'order' => 1,
        ]);
        // Creation already logged one "Unit published".
        $this->assertSame(1, \Spatie\Activitylog\Models\Activity::where('description', 'Unit published')->count());

        $this->unitRepo()->update($unit, ['is_published' => false]);

        // Unpublishing must not add another "published" entry.
        $this->assertSame(1, \Spatie\Activitylog\Models\Activity::where('description', 'Unit published')->count());
    }

    // ─── (c) File uploaded to a unit ────────────────────────────────────

    public function test_case_c_file_uploaded_to_unit(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);
        $unit = $this->unitRepo()->create([
            'course_id' => $course->id, 'title' => 'Cell Biology', 'content' => 'x', 'order' => 1,
        ]);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->image('diagram.png')],
            'App\Models\Unit',
            $unit->id,
            $teacher->id
        );

        $response = $this->dashboard($student);

        $response->assertOk();
        $response->assertSee('<strong>Ms. Rivera</strong>', false);
        $response->assertSee('an image');
        $response->assertSee('diagram.png');
        $response->assertSee('<strong>Cell Biology</strong>', false);
        $response->assertSee('<strong>Biology 101</strong>', false);
    }

    // ─── Scoping: active enrollment only ────────────────────────────────

    public function test_student_only_sees_notifications_for_actively_enrolled_courses(): void
    {
        Storage::fake('private');
        $teacher   = $this->teacher();
        $student   = $this->student();
        $courseA   = $this->course($teacher, 'Course A');
        $courseB   = $this->course($teacher, 'Course B');
        $this->enrollActive($teacher, $student, $courseA);
        // Enrolled in B but INACTIVE (e.g. kicked from just that course) — already in the
        // same teacher's class (teacher_student attached above), just add the course_student row.
        $student->enrolledCourses()->attach($courseB->id, ['is_active' => false, 'enrolled_at' => now()]);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'App\Models\Course', $courseA->id, $teacher->id
        );
        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')], 'App\Models\Course', $courseB->id, $teacher->id
        );

        $response = $this->dashboard($student);

        $response->assertOk();
        $response->assertSee('a.pdf');
        $response->assertDontSee('b.pdf');
    }

    public function test_student_does_not_see_another_students_or_other_courses_activity(): void
    {
        Storage::fake('private');
        $teacher  = $this->teacher();
        $studentA = $this->student();
        $studentB = User::factory()->student()->create(['name' => 'Jordan']);
        $courseA  = $this->course($teacher, 'Course A');
        $courseB  = $this->course($teacher, 'Course B');
        $this->enrollActive($teacher, $studentA, $courseA);
        $this->enrollActive($teacher, $studentB, $courseB);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('only-b.pdf', 10, 'application/pdf')], 'App\Models\Course', $courseB->id, $teacher->id
        );

        $response = $this->dashboard($studentA);

        $response->assertOk();
        $response->assertDontSee('only-b.pdf');
    }

    public function test_non_allowlisted_activity_does_not_appear_on_student_feed(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        activity()
            ->withProperties(['course_id' => $course->id])
            ->log('Some unrelated future event type');

        $response = $this->dashboard($student);

        $response->assertOk();
        $response->assertDontSee('Some unrelated future event type');
    }

    // ─── Graceful degradation: file deleted before student clicks ──────

    public function test_clicking_a_notification_link_for_a_deleted_file_shows_friendly_message_not_error(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('doomed.pdf', 10, 'application/pdf')], 'App\Models\Course', $course->id, $teacher->id
        );
        $file = File::where('original_name', 'doomed.pdf')->firstOrFail();

        // Notification still shows the file as a link before deletion.
        $before = $this->dashboard($student);
        $before->assertSee('doomed.pdf');

        // Teacher deletes the file (soft delete).
        $file->delete();

        // Notification message itself survives — permanent scalar snapshot.
        $after = $this->dashboard($student);
        $after->assertOk();
        $after->assertSee('doomed.pdf');

        // Clicking through must not crash or 404 — friendly message instead.
        $this->withoutExceptionHandling();
        try {
            $click = $this->actingAs($student)->get(route('files.download', $file->id));
            $click->assertSessionHas('error', 'This file has been removed by the teacher.');
        } catch (\Throwable $e) {
            $this->fail('Expected a graceful redirect, got an exception: ' . $e->getMessage());
        }
    }

    // ─── Doesn't affect teacher feed or admin log ──────────────────────

    public function test_student_activity_events_do_not_leak_into_teacher_feed(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);
        $this->enrollActive($teacher, $student, $course);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'App\Models\Course', $course->id, $teacher->id
        );

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertDontSee('a.pdf');
    }

    public function test_admin_activity_log_export_still_contains_the_raw_entries(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $course  = $this->course($teacher);
        $admin   = User::factory()->create([
            'role_id'   => \App\Models\Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);

        $this->fileRepo()->storeUploads(
            [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'App\Models\Course', $course->id, $teacher->id
        );

        $this->assertDatabaseHas('activity_log', ['description' => 'File uploaded to course']);

        $export = $this->actingAs($admin)->get(route('admin.logs.export'));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('File uploaded to course', $csv);
    }
}
