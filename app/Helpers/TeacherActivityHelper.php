<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Shared by Teacher\DashboardController (the "Recent Activity" card, latest 20) and
 * Teacher\ActivityController (the full "All Activity" page) — one allowlist, one query
 * builder, so the two views can never drift out of sync with each other.
 */
class TeacherActivityHelper
{
    /** Allowlist, not a denylist — a description not on this list never renders for a
     *  teacher, so a new event type added elsewhere can't leak in by default. */
    public const ALLOWED_DESCRIPTIONS = [
        'Student joined teacher class via class token',
        'Student enrolled in course via course token',
        'Course token expired: max uses reached',
        'Class token expired: max uses reached',
        'Course token expired: time limit reached',
        'Class token expired: time limit reached',
        'Course token revoked',
        'Class token revoked',
    ];

    /** Groups the allowlisted descriptions into the categories a teacher actually
     *  thinks in terms of, for the "Type" filter dropdown. "Revoked" is its own
     *  category, distinct from "expired" — a revoke is a deliberate teacher action,
     *  not the token naturally aging out or maxing out. */
    public const TYPE_FILTERS = [
        'joined_class'       => ['Student joined teacher class via class token'],
        'joined_course'      => ['Student enrolled in course via course token'],
        'expired_max_uses'   => ['Course token expired: max uses reached', 'Class token expired: max uses reached'],
        'expired_time_limit' => ['Course token expired: time limit reached', 'Class token expired: time limit reached'],
        'revoked'            => ['Course token revoked', 'Class token revoked'],
    ];

    public const TYPE_LABELS = [
        ''                    => 'All activity',
        'joined_class'        => 'Joined class',
        'joined_course'       => 'Joined course',
        'expired_max_uses'    => 'Token expired (max uses)',
        'expired_time_limit'  => 'Token expired (time limit)',
        'revoked'             => 'Token revoked',
    ];

    /** Scoped by properties->teacher_id (a JSON column) rather than causer — the
     *  expiry-notification cases have no student causer at all, so causer-based
     *  scoping can't cover them, but every allowlisted entry stores teacher_id. */
    public static function scopedQuery(int $teacherId): Builder
    {
        return Activity::with('causer')
            ->where('properties->teacher_id', $teacherId)
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

    /** Matches the student's name (causer) or the token/course values stored in
     *  properties — covers every field a teacher would actually recognize and search
     *  for. Blank $search is a no-op. */
    public static function applySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->whereHas('causer', fn (Builder $causer) => $causer->where('name', 'like', "%{$search}%"))
                ->orWhere('properties->token_value', 'like', "%{$search}%")
                ->orWhere('properties->course_title', 'like', "%{$search}%");
        });
    }
}
