<?php

namespace App\Helpers;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Student-facing counterpart to TeacherActivityHelper — same allowlist approach, same
 * bold-{value}-interpolation rendering convention, scoped to courses the student is
 * CURRENTLY actively enrolled in (course_student.is_active = true) rather than by
 * teacher_id, since a student's feed is naturally per-course, not per-teacher. A student
 * kicked from a course immediately loses ALL notifications for it, past and future —
 * a deliberate simplicity choice, not an oversight: retaining a kicked student's
 * pre-kick notification history would require snapshotting enrollment state per log
 * entry, which nothing else in this app currently does.
 */
class StudentActivityHelper
{
    /** Allowlist, not a denylist — a description not on this list never renders for a
     *  student, so a new event type added elsewhere can't leak in by default. */
    public const ALLOWED_DESCRIPTIONS = [
        'File uploaded to course',
        'File uploaded to unit',
        'Unit published',
    ];

    /** Groups the allowlisted descriptions into the categories a student actually
     *  thinks in terms of, for the "Type" filter dropdown. */
    public const TYPE_FILTERS = [
        'file_course'    => ['File uploaded to course'],
        'file_unit'      => ['File uploaded to unit'],
        'unit_published' => ['Unit published'],
    ];

    public const TYPE_LABELS = [
        ''                => 'All activity',
        'file_course'     => 'New course material',
        'file_unit'       => 'New unit material',
        'unit_published'  => 'New units',
    ];

    /** Scoped by properties->course_id (a JSON column), restricted to courses the
     *  student currently has an ACTIVE course_student enrollment for — same scoping
     *  query already used by EloquentCourseRepository::getEnrolledByStudent(). */
    public static function scopedQuery(int $studentId): Builder
    {
        $activeCourseIds = Course::whereHas(
            'students',
            fn ($q) => $q->where('student_id', $studentId)->where('course_student.is_active', true)
        )->pluck('id');

        return Activity::whereIn('properties->course_id', $activeCourseIds)
            ->whereIn('description', self::ALLOWED_DESCRIPTIONS)
            // orderByDesc(created_at) alone isn't a unique enough order for reliable
            // cursor pagination (ties on the same timestamp) — id breaks ties.
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** Narrows an already-scoped query to one of TYPE_FILTERS' categories. Unknown/blank
     *  $type is a no-op (matches everything the allowlist already allows). */
    public static function applyType(Builder $query, ?string $type): Builder
    {
        if ($type && isset(self::TYPE_FILTERS[$type])) {
            $query->whereIn('description', self::TYPE_FILTERS[$type]);
        }

        return $query;
    }

    /** Matches the teacher's name, the course/unit name, or the file name stored in
     *  properties — covers every field a student would actually recognize and search
     *  for. Blank $search is a no-op. */
    public static function applySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('properties->teacher_name', 'like', "%{$search}%")
                ->orWhere('properties->course_name', 'like', "%{$search}%")
                ->orWhere('properties->unit_name', 'like', "%{$search}%")
                ->orWhere('properties->file_name', 'like', "%{$search}%");
        });
    }

    /** Human-friendly, article-correct descriptor from a stored MIME type — computed at
     *  render time from the permanently-stored scalar, not stored pre-rendered, so
     *  phrasing can still improve later without touching historical log rows. */
    public static function describeFileType(?string $mimeType): string
    {
        return match (true) {
            $mimeType === null                                  => 'a file',
            str_starts_with($mimeType, 'image/')                => 'an image',
            str_starts_with($mimeType, 'video/')                => 'a video',
            $mimeType === 'application/pdf'                      => 'a PDF',
            str_contains($mimeType, 'wordprocessingml')
                || $mimeType === 'application/msword'            => 'a document',
            str_contains($mimeType, 'spreadsheetml')
                || $mimeType === 'application/vnd.ms-excel'      => 'a spreadsheet',
            str_contains($mimeType, 'presentationml')
                || $mimeType === 'application/vnd.ms-powerpoint' => 'a presentation',
            $mimeType === 'application/zip'                      => 'a zip archive',
            $mimeType === 'text/plain'                           => 'a text file',
            default                                              => 'a file',
        };
    }
}
