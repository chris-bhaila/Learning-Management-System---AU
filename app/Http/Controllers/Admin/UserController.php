<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index(Request $request)
    {
        $role   = in_array($request->get('role'), ['admin', 'teacher', 'student'])
                    ? $request->get('role')
                    : 'admin';
        $sort   = in_array($request->get('sort'), ['recent', 'oldest', 'az'])
                    ? $request->get('sort')
                    : 'recent';
        $status = in_array($request->get('status'), ['active', 'inactive'])
                    ? $request->get('status')
                    : null;
        $search = $request->get('search');

        // Only a Super Admin viewer sees other super_admin-role accounts folded into the
        // Admins tab — a regular Admin's view of that tab is unchanged.
        $viewerIsSuperAdmin = Auth::user()->isSuperAdmin();

        $roleCounts = $this->users->getRoleCounts();
        if ($viewerIsSuperAdmin) {
            $roleCounts['admin'] = ($roleCounts['admin'] ?? 0) + ($roleCounts['super_admin'] ?? 0);
        }

        $data = [
            'users'          => $this->users->getFilteredUsers($role, $sort, $search, $status, 20, $viewerIsSuperAdmin),
            'roleCounts'     => $roleCounts,
            'roles'          => $this->roles->all(),
            'currentRole'    => $role,
            'currentSort'    => $sort,
            'currentStatus'  => $status,
            'currentSearch'  => $search ?? '',
        ];

        if ($request->ajax()) {
            return view('admin.users._table', $data);
        }

        return view('admin.users.index', $data);
    }

    public function show(int $id)
    {
        $user = $this->users->find($id);
        abort_if(is_null($user), 404);

        if ($user->isTeacher()) {
            $user->load(['courses' => fn($q) => $q->withCount('students')->with('units')]);
        } elseif ($user->isStudent()) {
            $user->setRelation('enrolledTeachers', $this->users->getTeachersForStudent($user->id));
        }

        return view('admin.users.show', [
            'subject' => $user,
        ]);
    }

    public function showStudentClass(int $userId, int $teacherId)
    {
        $student = $this->users->getStudentWithTeacherPivot($userId, $teacherId);
        abort_if(is_null($student), 404);

        $teacher = $this->users->find($teacherId);
        abort_if(is_null($teacher), 404);

        return view('admin.users.student-class', [
            'student' => $student,
            'teacher' => $teacher,
            'courses' => $this->courses->getStudentCoursesForTeacher($userId, $teacherId),
        ]);
    }

    /** Admin variant of Teacher\StudentController::kickFromClass() — $teacherId is explicit
     *  (from the route, not Auth::id()) since Admin acts on a specific teacher's class. */
    public function kickStudentFromClass(int $userId, int $teacherId)
    {
        $student = $this->users->find($userId);
        abort_if(is_null($student), 404);

        $teacher = $this->users->find($teacherId);
        abort_if(is_null($teacher), 404);

        $this->authorize('kickFromClass', [$student, $teacherId]);

        $this->users->kickFromClass($teacherId, $student->id);

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'student_id'   => $student->id,
                'student_name' => $student->name,
                'teacher_id'   => $teacher->id,
                'teacher_name' => $teacher->name,
                'scope'        => 'class',
            ])
            ->log('Teacher kicked student from class');

        return redirect()->route('admin.users.show', $student->id)
            ->with('success', "{$student->name} has been removed from {$teacher->name}'s class.");
    }

    public function store(StoreUserRequest $request)
    {
        $roleName = $request->validated('role');
        $role     = $this->roles->findByName($roleName);

        $newUser = $this->users->create([
            'name'      => $request->validated('name'),
            'email'     => $request->validated('email'),
            'role_id'   => $role->id,
            'password'  => $request->validated('password'), // hashed by User model cast
            'is_active' => true,
        ]);

        // Privilege-sensitive event, same category as promoteToAdmin() — logged in addition
        // to Spatie's automatic "created" log (User uses LogsActivity), since that generic
        // log doesn't call out "this was created directly as admin" specifically.
        if ($roleName === 'admin') {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'new_user_id'    => $newUser->id,
                    'new_user_name'  => $newUser->name,
                    'new_user_email' => $newUser->email,
                ])
                ->log('Created new admin account');
        }

        return back()->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->users->find($id);
        abort_if(is_null($user), 404);

        $this->authorize('update', $user);

        // A Super Admin editing their own row (the one case update() allows for a
        // super_admin target — see UserPolicy::update()) must never be able to lock
        // themselves out by deactivating their own account through this form.
        if ($user->id === Auth::id() && $user->isSuperAdmin() && !$request->validated('is_active')) {
            return back()->with('error', 'You cannot deactivate your own Super Admin account.');
        }

        $updates = [
            'name'      => $request->validated('name'),
            'is_active' => $request->validated('is_active'),
        ];

        // Admin/Super Admin role is locked — never allow role changes on an already-
        // privileged account. And this generic endpoint must never be usable to grant
        // admin to a non-admin target — that's an exclusive, separately-policy-gated
        // action (see promoteToAdmin()), not something reachable via a crafted request
        // here even though the FormRequest's format validation still nominally accepts
        // "admin" as a shape-valid value for the admin's-own-profile-edit case above.
        if (!$user->isAdmin()) {
            $requestedRole = $request->validated('role');
            abort_if($requestedRole === 'admin', 403);
            $updates['role_id'] = $this->roles->findByName($requestedRole)->id;
        }

        $this->users->update($user, $updates);

        // Avatar — handled in same form submission as the profile update.
        if ($request->hasFile('avatar')) {
            $path = $this->resizeAndStore($request->file('avatar'));
            $this->users->updateAvatar($user->fresh(), $path);
        } elseif ($request->boolean('remove_avatar')) {
            $this->users->removeAvatar($user->fresh());
        }

        return back()->with('success', 'User updated.');
    }

    /** Exclusive to Super Admin (enforced by the Policy, not just hidden in the UI) —
     *  grants the admin role to a teacher/student. See UserPolicy::promoteToAdmin(). */
    public function promoteToAdmin(int $id)
    {
        $target = $this->users->find($id);
        abort_if(is_null($target), 404);

        $this->authorize('promoteToAdmin', $target);

        $oldRole   = $target->role->name;
        $adminRole = $this->roles->findByName('admin');

        $this->users->update($target, ['role_id' => $adminRole->id]);

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'target_user_id'   => $target->id,
                'target_user_name' => $target->name,
                'old_role'         => $oldRole,
                'new_role'         => 'admin',
            ])
            ->log('Granted admin role to user');

        return back()->with('success', "{$target->name} has been promoted to Admin.");
    }

    /** Exclusive to Super Admin (enforced by the Policy, not just hidden in the UI) —
     *  demotes an existing admin down to teacher or student, picked at demotion time.
     *  Deliberately a separate endpoint from update(): that endpoint's role handling is
     *  built defensively around a narrower case (blocking escalation to admin) and was
     *  never meant to carry a demotion path — mixing the two risks the same kind of gap
     *  the mass-assignment role_id issue exposed. See UserPolicy::demoteAdmin(). */
    public function demoteAdmin(UpdateUserRoleRequest $request, int $id)
    {
        $target = $this->users->find($id);
        abort_if(is_null($target), 404);

        $newRole = $request->validated('role');

        $this->authorize('demoteAdmin', [$target, $newRole]);

        $oldRole = $target->role->name;
        $role    = $this->roles->findByName($newRole);

        $this->users->update($target, ['role_id' => $role->id]);

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'target_user_id'   => $target->id,
                'target_user_name' => $target->name,
                'old_role'         => $oldRole,
                'new_role'         => $newRole,
            ])
            ->log('Demoted admin role to user');

        return back()->with('success', "{$target->name} has been demoted to " . ucfirst($newRole) . '.');
    }

    public function destroy(int $id)
    {
        $user = $this->users->find($id);
        abort_if(is_null($user), 404);

        $this->authorize('delete', $user);

        $this->users->delete($user);

        return back()->with('success', 'User deleted.');
    }

    private function resizeAndStore(\Illuminate\Http\UploadedFile $file): string
    {
        $manager  = new ImageManager(new Driver());
        $image    = $manager->decode($file->getRealPath());
        $image->cover(256, 256);
        $encoded  = $image->encode(new WebpEncoder(90));

        $path = 'avatars/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
