<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivityLogHelper
{
    /**
     * Human-readable labels for every column that appears in activity_changes
     * across all logged models (User, Course, Unit, Token, CourseGroup, File).
     */
    private const LABELS = [
        // ─── Shared ───────────────────────────────────────────────
        'name'               => 'Name',
        'title'              => 'Title',
        'description'        => 'Description',
        'created_at'         => 'Created',
        'updated_at'         => 'Updated',
        'deleted_at'         => 'Deleted At',

        // ─── User ─────────────────────────────────────────────────
        'email'              => 'Email',
        'password'           => 'Password',
        'role_id'            => 'Role',
        'google_id'          => 'Google Account',
        'avatar'             => 'Avatar URL',
        'avatar_path'        => 'Profile Photo',
        'avatar_source'      => 'Photo Source',
        'is_active'          => 'Active',

        // ─── Course ───────────────────────────────────────────────
        'teacher_id'         => 'Teacher',
        'group_id'           => 'Group',
        'is_published'       => 'Published Status',

        // ─── Unit ─────────────────────────────────────────────────
        'course_id'          => 'Course',
        'content'            => 'Content',
        'order'              => 'Position',

        // ─── Token ────────────────────────────────────────────────
        'token_value'        => 'Token',
        'type'               => 'Type',
        'expires_at'         => 'Expires',
        'max_uses'           => 'Max Uses',
        'uses_count'         => 'Uses',

        // ─── CourseGroup ──────────────────────────────────────────
        // (name, description, teacher_id already covered above)

        // ─── File ─────────────────────────────────────────────────
        'original_name'      => 'File Name',
        'filename'           => 'Stored As',
        'path'               => 'Storage Path',
        'mime_type'          => 'File Type',
        'size'               => 'File Size',
        'fileable_type'      => 'Attached To',
        'fileable_id'        => 'Attachment',
        'uploaded_by'        => 'Uploaded By',
    ];

    /**
     * Request-scoped cache to avoid repeated FK lookups for the same ID.
     * Keyed as "ClassName:id".
     */
    private static array $cache = [];

    public static function fieldLabel(string $column): string
    {
        return self::LABELS[$column] ?? Str::title(str_replace('_', ' ', $column));
    }

    /**
     * Format a single attribute value for human display.
     * Strips HTML from rich-text fields, resolves foreign keys, humanises booleans.
     *
     * @param array $context  Full sibling attribute map (used for polymorphic FK resolution).
     */
    public static function formatValue(string $column, mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '—';
        }

        // ── Passwords: never expose the hash ──────────────────────────
        if ($column === 'password') {
            return '(hidden)';
        }

        // ── Booleans / boolean-like columns ───────────────────────────
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (in_array($column, ['is_active', 'is_published'], true)) {
            return $value ? 'Yes' : 'No';
        }

        // ── avatar_source enum ────────────────────────────────────────
        if ($column === 'avatar_source') {
            return match ((string) $value) {
                'upload' => 'Custom upload',
                'google' => 'From Google',
                'none'   => 'None (initials)',
                default  => (string) $value,
            };
        }

        // ── avatar_path: show filename only, not storage path ─────────
        if ($column === 'avatar_path') {
            return basename((string) $value);
        }

        // ── Token type ────────────────────────────────────────────────
        if ($column === 'type') {
            return match ((string) $value) {
                'class'  => 'Class token',
                'course' => 'Course token',
                default  => (string) $value,
            };
        }

        // ── File size: human-readable bytes ───────────────────────────
        if ($column === 'size' && is_numeric($value)) {
            return self::formatBytes((int) $value);
        }

        // ── fileable_type: strip namespace ────────────────────────────
        if ($column === 'fileable_type') {
            return class_basename((string) $value);
        }

        // ── Foreign keys: resolve to a name ───────────────────────────
        if ($column === 'role_id') {
            return self::resolve(\App\Models\Role::class, $value, 'name')
                ?? 'Role #' . $value;
        }
        if ($column === 'teacher_id' || $column === 'uploaded_by') {
            return self::resolve(\App\Models\User::class, $value, 'name')
                ?? 'User #' . $value;
        }
        if ($column === 'course_id') {
            return self::resolve(\App\Models\Course::class, $value, 'title')
                ?? 'Course #' . $value;
        }
        if ($column === 'group_id') {
            return self::resolve(\App\Models\CourseGroup::class, $value, 'name')
                ?? 'Group #' . $value;
        }
        if ($column === 'fileable_id') {
            // Polymorphic FK — resolve using fileable_type from the sibling attributes.
            $type = $context['fileable_type'] ?? null;
            if ($type) {
                $map = [
                    'App\\Models\\Course' => [\App\Models\Course::class, 'title'],
                    'App\\Models\\Unit'   => [\App\Models\Unit::class,   'title'],
                ];
                [$class, $attr] = $map[$type] ?? [null, null];
                if ($class) {
                    return self::resolve($class, $value, $attr) ?? class_basename($type) . ' #' . $value;
                }
            }
            return '#' . $value;
        }

        // ── Rich-text fields: strip HTML then truncate ────────────────
        // Replace block-closing tags with a space first so words from adjacent
        // elements don't run together after tag removal, then decode entities.
        if (in_array($column, ['content', 'description'], true)) {
            $spaced = preg_replace('/<\/(p|li|h[1-6]|div|td|th)>/i', ' ', (string) $value);
            $plain  = html_entity_decode(
                trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $spaced))),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            return $plain === '' ? '(empty)' : Str::limit($plain, 120);
        }

        // ── Google ID: mask most of the value ────────────────────────
        if ($column === 'google_id') {
            $s = (string) $value;
            return strlen($s) > 8 ? substr($s, 0, 4) . '…' . substr($s, -4) : $s;
        }

        // ── Default: plain string, truncated ─────────────────────────
        return Str::limit((string) $value, 100);
    }

    /**
     * Build a structured diff array ready for Blade / Alpine consumption.
     * Each row: ['label' => '…', 'old' => '…', 'new' => '…']
     * For created events 'old' is null; for deleted events 'new' is null.
     */
    public static function buildDiff(
        array $attributes,
        array $old,
        string $event
    ): array {
        $rows = [];

        if ($event === 'deleted') {
            // Spatie stores old values only for deletes
            foreach ($old as $col => $val) {
                $rows[] = [
                    'label' => self::fieldLabel($col),
                    'old'   => self::formatValue($col, $val, $old),
                    'new'   => null,
                ];
            }
            return $rows;
        }

        foreach ($attributes as $col => $newVal) {
            $rows[] = [
                'label' => self::fieldLabel($col),
                'old'   => isset($old[$col]) ? self::formatValue($col, $old[$col], $old) : null,
                'new'   => self::formatValue($col, $newVal, $attributes),
            ];
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────

    /** Memoised model lookup. Uses withTrashed() when the model has SoftDeletes. */
    private static function resolve(string $class, mixed $id, string $attribute): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        $key = $class . ':' . $id;

        if (! array_key_exists($key, self::$cache)) {
            $usesTrashed = in_array(SoftDeletes::class, class_uses_recursive($class), true);
            $model = $usesTrashed
                ? $class::withTrashed()->find((int) $id)
                : $class::find((int) $id);
            self::$cache[$key] = $model?->$attribute;
        }

        return self::$cache[$key] !== null ? (string) self::$cache[$key] : null;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1_048_576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1_048_576, 1) . ' MB';
    }
}
