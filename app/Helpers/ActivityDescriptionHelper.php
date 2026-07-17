<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Builds one plain-text, human-readable sentence per Activity row — currently only
 * consumed by Admin\ActivityLogController::export() (the CSV "Description" column).
 *
 * The wording for the allowlisted event types below is deliberately kept in lockstep
 * with teacher/activity/_item.blade.php and student/activity/_item.blade.php (same
 * facts, same phrasing) — just flattened to plain text (no <strong> markup) and
 * rephrased from second-person ("your class") to third-person, since this helper
 * serves an Admin-wide export that isn't scoped to a single teacher/student's own
 * feed the way those two partials are. If wording changes in either partial, mirror
 * it here too.
 *
 * All property access goes through prop()/self::$p->get(), never bare array-offset —
 * Illuminate\Support\Collection::offsetGet() indexes the underlying array directly and
 * throws on a missing key (unlike ->get(), which returns a default). Several real,
 * legitimately-shaped rows in this app omit keys a naive reading of the matching Blade
 * partial would assume are always present (e.g. token expiry/revocation events never
 * carry teacher_name, only teacher_id — see EloquentTokenRepository::logExpiry()/
 * revoke()), so every access here must tolerate a missing key.
 */
class ActivityDescriptionHelper
{
    public static function describe(Activity $activity): string
    {
        $builder = self::builders()[$activity->description] ?? null;

        if ($builder) {
            $p = $activity->properties ?? collect();

            return $builder($activity, $p, self::causerName($activity));
        }

        return self::describeGeneric($activity);
    }

    private static function causerName(Activity $activity): string
    {
        return $activity->causer?->name ?? 'A student';
    }

    /** Token lifecycle events (expiry/revocation) only ever store teacher_id, never
     *  teacher_name — resolved here the same way ActivityLogHelper::formatValue()
     *  resolves other stored foreign keys elsewhere in this file. */
    private static function teacherName(Collection $p): string
    {
        $id = $p->get('teacher_id');

        return $id ? (User::find($id)?->name ?? 'Unknown') : 'Unknown';
    }

    /**
     * @return array<string, callable(Activity, Collection, string): string>
     */
    private static function builders(): array
    {
        return [
            // ── Teacher notification feed (TeacherActivityHelper::ALLOWED_DESCRIPTIONS) ──
            'Student joined teacher class via class token' => fn ($a, $p, $who) =>
                "{$who} joined {$p->get('teacher_name', 'Unknown')}'s class",

            'Student enrolled in course via course token' => fn ($a, $p, $who) =>
                "{$who} joined {$p->get('teacher_name', 'Unknown')}'s course \"{$p->get('course_title', 'Unknown')}\" via token {$p->get('token_value', '—')}",

            'Course token expired: max uses reached' => fn ($a, $p) =>
                "Course token {$p->get('token_value', '—')} for \"{$p->get('course_title', 'Unknown')}\" (teacher: " . self::teacherName($p) . ") expired — max uses ({$p->get('max_uses', '—')}) reached",

            'Class token expired: max uses reached' => fn ($a, $p) =>
                "Class token {$p->get('token_value', '—')} for " . self::teacherName($p) . "'s class expired — max uses ({$p->get('max_uses', '—')}) reached",

            'Course token expired: time limit reached' => fn ($a, $p) =>
                "Course token {$p->get('token_value', '—')} for \"{$p->get('course_title', 'Unknown')}\" (teacher: " . self::teacherName($p) . ") expired — time limit reached",

            'Class token expired: time limit reached' => fn ($a, $p) =>
                "Class token {$p->get('token_value', '—')} for " . self::teacherName($p) . "'s class expired — time limit reached",

            'Course token revoked' => fn ($a, $p) =>
                "Course token {$p->get('token_value', '—')} for \"{$p->get('course_title', 'Unknown')}\" (teacher: " . self::teacherName($p) . ") was revoked",

            'Class token revoked' => fn ($a, $p) =>
                "Class token {$p->get('token_value', '—')} for " . self::teacherName($p) . "'s class was revoked",

            // ── Student notification feed (StudentActivityHelper::ALLOWED_DESCRIPTIONS) ──
            'File uploaded to course' => fn ($a, $p) =>
                "{$p->get('teacher_name', 'Unknown')} uploaded " . StudentActivityHelper::describeFileType($p->get('file_type'))
                    . " \"{$p->get('file_name', 'Unknown')}\" to course \"{$p->get('course_name', 'Unknown')}\"",

            'File uploaded to unit' => fn ($a, $p) =>
                "{$p->get('teacher_name', 'Unknown')} uploaded " . StudentActivityHelper::describeFileType($p->get('file_type'))
                    . " \"{$p->get('file_name', 'Unknown')}\" to unit \"{$p->get('unit_name', 'Unknown')}\" of course \"{$p->get('course_name', 'Unknown')}\"",

            'Unit published' => fn ($a, $p) =>
                "{$p->get('teacher_name', 'Unknown')} published unit \"{$p->get('unit_name', 'Unknown')}\" in course \"{$p->get('course_name', 'Unknown')}\"",

            // ── Enrollment/roster management (not in either notification allowlist) ──
            'Teacher removed student from course' => fn ($a, $p) =>
                "{$p->get('teacher_name', 'Unknown')} removed {$p->get('student_name', 'Unknown')} from course \"{$p->get('course_title', 'Unknown')}\"",

            'Teacher kicked student from class' => fn ($a, $p) =>
                "{$p->get('teacher_name', 'Unknown')} removed {$p->get('student_name', 'Unknown')} from their class",

            // ── Failed enrollment attempts ──
            'Student enrollment failed: token not found' => fn ($a, $p, $who) =>
                "{$who} attempted to enroll with token \"{$p->get('token_value', '—')}\" — token not found",

            'Student enrollment failed: token expired' => fn ($a, $p, $who) =>
                "{$who} attempted to use token \"{$p->get('token_value', '—')}\" — token had already expired",

            'Student enrollment failed: already in class' => fn ($a, $p, $who) =>
                "{$who} attempted to rejoin {$p->get('teacher_name', 'Unknown')}'s class with token \"{$p->get('token_value', '—')}\" — already a member",

            'Student enrollment failed: not in teacher class' => fn ($a, $p, $who) =>
                "{$who} attempted to use course token \"{$p->get('token_value', '—')}\" — not yet a member of {$p->get('teacher_name', 'Unknown')}'s class",

            'Student enrollment failed: already enrolled in course' => fn ($a, $p, $who) =>
                "{$who} attempted to enroll in \"{$p->get('course_title', 'Unknown')}\" with token \"{$p->get('token_value', '—')}\" — already enrolled",

            // ── Auth ──
            'Restored previously-deleted account on Google sign-in' => fn ($a, $p, $who) =>
                "{$who} restored their deleted account by signing in with Google",

            'Signed in via Google' => fn ($a, $p, $who) =>
                "{$who} signed in via Google",

            'Signed in' => fn ($a, $p, $who) =>
                "{$who} signed in with a password",

            'Signed out' => fn ($a, $p, $who) =>
                "{$who} signed out",

            // ── Admin/Super Admin account management ──
            'Created new admin account' => fn ($a, $p, $who) =>
                "{$who} created a new admin account for {$p->get('new_user_name', 'Unknown')} ({$p->get('new_user_email', 'Unknown')})",

            'Granted admin role to user' => fn ($a, $p, $who) =>
                "{$who} granted the Admin role to {$p->get('target_user_name', 'Unknown')} (was {$p->get('old_role', 'Unknown')})",

            'Demoted admin role to user' => fn ($a, $p, $who) =>
                "{$who} demoted {$p->get('target_user_name', 'Unknown')} from Admin to " . Str::title((string) $p->get('new_role', 'Unknown')),
        ];
    }

    /**
     * Fallback for events not covered above — generic model create/update/delete/restore
     * logged automatically via each model's LogsActivity trait, where Spatie's default
     * description is just the bare event name ("created"/"updated"/…). Reuses
     * ActivityLogHelper::buildDiff()/formatValue() for the changed-field summary and
     * label resolution — the exact same redaction (password → "(hidden)", etc.) as the
     * on-screen Event Details modal and the old export's attribute-change columns.
     */
    private static function describeGeneric(Activity $activity): string
    {
        $who = $activity->causer?->name ?? 'System';
        $event = $activity->event ?? 'updated';
        $subjectTypeLabel = $activity->subject_type ? class_basename($activity->subject_type) : 'record';

        $changes = $activity->attribute_changes;
        $newAttrs = (array) ($changes?->get('attributes') ?? []);
        $oldAttrs = (array) ($changes?->get('old') ?? []);
        $diff = ActivityLogHelper::buildDiff($newAttrs, $oldAttrs, $event);

        $label = self::subjectLabel($activity, $diff);
        $subjectDescriptor = "{$subjectTypeLabel} \"{$label}\"";

        return match ($event) {
            'created'  => "{$who} created {$subjectDescriptor}",
            'deleted'  => "{$who} deleted {$subjectDescriptor}",
            'restored' => "{$who} restored {$subjectDescriptor}",
            'updated'  => self::describeUpdate($who, $subjectDescriptor, $diff),
            default    => "{$who} performed \"{$event}\" on {$subjectDescriptor}",
        };
    }

    private static function describeUpdate(string $who, string $subjectDescriptor, array $diff): string
    {
        $fields = array_column($diff, 'label');

        if (empty($fields)) {
            return "{$who} updated {$subjectDescriptor}";
        }

        return "{$who} updated {$subjectDescriptor}: " . implode(', ', $fields) . ' changed';
    }

    /** Prefers the live subject relation; falls back to a name-ish field captured in
     *  the diff itself (needed for 'deleted' events on soft-deleting models — the
     *  subject's morphTo() query excludes the now-trashed row by Eloquent's default
     *  scope, exactly like the on-screen page's own subjectName fallback handles). */
    private static function subjectLabel(Activity $activity, array $diff): string
    {
        $subject = $activity->subject;
        $live = $subject?->name ?? $subject?->title ?? $subject?->token_value ?? null;

        if ($live !== null) {
            return (string) $live;
        }

        foreach (['Name', 'Title', 'Token'] as $preferred) {
            foreach ($diff as $row) {
                if ($row['label'] === $preferred) {
                    return (string) ($row['new'] ?? $row['old'] ?? ('#' . $activity->subject_id));
                }
            }
        }

        return '#' . $activity->subject_id;
    }
}
