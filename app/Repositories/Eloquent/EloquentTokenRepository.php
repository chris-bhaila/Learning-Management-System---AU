<?php

namespace App\Repositories\Eloquent;

use App\Models\Token;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentTokenRepository implements TokenRepositoryInterface
{
    public function find(int $id): ?Token
    {
        return Token::find($id);
    }

    public function findByValue(string $value): ?Token
    {
        return Token::whereRaw('UPPER(token_value) = ?', [strtoupper($value)])->first();
    }

    public function create(array $data): Token
    {
        return Token::create($data);
    }

    public function getActiveByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('expires_at', '>', now())
            ->whereNull('revoked_at')
            ->get();
    }

    public function getExpiredUnnotified(): Collection
    {
        // whereNull('revoked_at') is the fix that matters here: without it, a token
        // revoked well before its natural expires_at would still surface here once that
        // time eventually passed, producing a misleading "Time limit reached!"
        // notification for a token that was actually manually revoked, not naturally expired.
        return Token::where('expires_at', '<=', now())
            ->where('expiry_notified', false)
            ->whereNull('revoked_at')
            ->get();
    }

    public function incrementUses(Token $token): void
    {
        $token->incrementUses();

        // Checked right here, at the exact moment uses_count crosses max_uses — not
        // derived later — per the real trigger point already in the enrollment success
        // path. expiry_notified guards against double-logging (also shared with the
        // time-limit trigger in NotifyExpiredTokens, since a token only meaningfully
        // "expires" once, however it gets there).
        if (! $token->expiry_notified && $token->uses_count >= $token->max_uses) {
            $this->logExpiry($token, 'max_uses');
        }
    }

    /** Shared by the max-uses trigger above and NotifyExpiredTokens' time-limit trigger —
     *  logs a permanent, self-contained snapshot (plain scalars, not live FK lookups) so
     *  the entry stays fully readable even after the token row is later pruned, matching
     *  the existing enrollment-success logging pattern. */
    public function logExpiry(Token $token, string $trigger): void
    {
        $token->loadMissing('course');

        $descriptor = $token->isCourseToken() ? 'Course' : 'Class';
        $reasonText = $trigger === 'max_uses' ? 'max uses reached' : 'time limit reached';

        $properties = [
            'token_value' => $token->token_value,
            'token_type'  => $token->type,
            'teacher_id'  => $token->teacher_id,
            'max_uses'    => $token->max_uses,
            'uses_count'  => $token->uses_count,
            'expires_at'  => optional($token->expires_at)->toISOString(),
            'trigger'     => $trigger,
        ];

        if ($token->isCourseToken()) {
            $properties['course_id']    = $token->course_id;
            $properties['course_title'] = $token->course?->title ?? 'Unknown';
        }

        activity()
            ->withProperties($properties)
            ->log("{$descriptor} token expired: {$reasonText}");

        // Token::getActivitylogOptions() excludes expiry_notified via logExcept(), so this
        // plain update doesn't create its own generic "updated" audit row alongside the
        // descriptive entry just logged above.
        $token->update(['expiry_notified' => true]);
    }

    /** Distinct from logExpiry() — a revoked token and a naturally expired token are
     *  different events with different causes, so this logs its own description
     *  ("Class/Course token revoked") rather than reusing the expiry wording, and must
     *  never be picked up again by getExpiredUnnotified() (guarded there via
     *  whereNull('revoked_at'), not by expiry_notified — a revoked token's expires_at
     *  may still be in the future, so the expiry_notified flag alone wouldn't help). */
    public function revoke(Token $token): void
    {
        // Idempotent — the UI hides the revoke action once a token is already revoked,
        // but this guards a crafted repeat request from re-logging a duplicate
        // "revoked" notification for the same token.
        if ($token->isRevoked()) {
            return;
        }

        $token->loadMissing('course');

        $descriptor = $token->isCourseToken() ? 'Course' : 'Class';

        $properties = [
            'token_value' => $token->token_value,
            'token_type'  => $token->type,
            'teacher_id'  => $token->teacher_id,
        ];

        if ($token->isCourseToken()) {
            $properties['course_id']    = $token->course_id;
            $properties['course_title'] = $token->course?->title ?? 'Unknown';
        }

        activity()
            ->withProperties($properties)
            ->log("{$descriptor} token revoked");

        $token->update(['revoked_at' => now()]);
    }

    public function getAll(): Collection
    {
        return Token::with('teacher', 'course')->get();
    }

    public function getClassTokensByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'class')
            ->latest()
            ->get();
    }

    public function getCourseTokensByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'course')
            ->with('course')
            ->latest()
            ->get();
    }

    public function getClassTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'class')
            ->latest()
            ->paginate($perPage);
    }

    public function getCourseTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'course')
            ->with('course')
            ->latest()
            ->paginate($perPage);
    }

    public function generateUniqueValue(string $type): string
    {
        $letters = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $length  = $type === 'class' ? 11 : 9;

        // At least 40%, at most 50% of characters are digits — picked as the single
        // integer digit count satisfying both bounds for the given token length.
        $minDigits  = (int) ceil($length * 0.4);
        $maxDigits  = (int) floor($length * 0.5);
        $digitCount = max($minDigits, min($maxDigits, (int) round($length * 0.45)));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $chars = [];
            for ($i = 0; $i < $digitCount; $i++) {
                $chars[] = $digits[random_int(0, strlen($digits) - 1)];
            }
            for ($i = 0; $i < $length - $digitCount; $i++) {
                $chars[] = $letters[random_int(0, strlen($letters) - 1)];
            }

            // Fisher–Yates shuffle (random_int, not shuffle()) so digit positions aren't predictable.
            for ($i = count($chars) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
            }

            $value = implode('', $chars);

            if (!Token::whereRaw('UPPER(token_value) = ?', [strtoupper($value)])->exists()) {
                return $value;
            }
        }

        throw new \RuntimeException('Failed to generate a unique token value after 5 attempts.');
    }
}