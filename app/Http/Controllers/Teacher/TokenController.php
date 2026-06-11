<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTokenRequest;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
        private CourseRepositoryInterface $courses,
    ) {}

    public function store(StoreTokenRequest $request)
    {
        $data = $request->validated();

        if ($data['type'] === 'course') {
            $course = $this->courses->find($data['course_id']);
            $this->authorize('update', $course);
        }

        $this->tokens->create([
            'teacher_id'  => Auth::id(),
            'course_id'   => $data['course_id'] ?? null,
            'token_value' => strtoupper(Str::random(12)),
            'type'        => $data['type'],
            'expires_at'  => now()->addMinutes($data['lifetime_minutes']),
            'max_uses'    => $data['max_uses'],
            'uses_count'  => 0,
        ]);

        return back()->with('success', 'Token generated.');
    }

    public function destroy(int $id)
    {
        $token = $this->tokens->find($id);
        $this->authorize('delete', $token);

        $this->tokens->delete($token);

        return back()->with('success', 'Token revoked.');
    }
}