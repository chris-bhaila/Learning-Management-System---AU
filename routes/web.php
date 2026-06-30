<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\Student;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SettingsController;

// Landing page
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->role->name . '.dashboard');
    }
    return view('landing');
})->name('home');

// Auth — Google OAuth (throttled: 10 requests / minute)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Email/password login (throttled: 5 attempts / minute)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{userId}/classes/{teacherId}', [Admin\UserController::class, 'showStudentClass'])->name('users.classes.show');

    // Courses
    Route::get('/courses', [Admin\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [Admin\CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [Admin\CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{id}', [Admin\CourseController::class, 'show'])->name('courses.show');
    Route::patch('/courses/{id}', [Admin\CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [Admin\CourseController::class, 'destroy'])->name('courses.destroy');

    // Units
    Route::get('/courses/{courseId}/units/create', [Admin\UnitController::class, 'create'])->name('units.create');
    Route::post('/courses/{courseId}/units', [Admin\UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{id}', [Admin\UnitController::class, 'show'])->name('units.show');
    Route::patch('/units/{id}', [Admin\UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{id}', [Admin\UnitController::class, 'destroy'])->name('units.destroy');

    // Tokens
    Route::get('/tokens', [Admin\TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [Admin\TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{id}', [Admin\TokenController::class, 'destroy'])->name('tokens.destroy');

    // Course Groups
    Route::get('/groups', [Admin\CourseGroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [Admin\CourseGroupController::class, 'store'])->name('groups.store');
    Route::patch('/groups/{id}', [Admin\CourseGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{id}', [Admin\CourseGroupController::class, 'destroy'])->name('groups.destroy');

    // Activity Logs
    Route::get('/logs', [Admin\ActivityLogController::class, 'index'])->name('logs.index');
});

// Teacher
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [Teacher\DashboardController::class, 'index'])->name('dashboard');

    // Courses
    Route::get('/courses', [Teacher\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [Teacher\CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [Teacher\CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{id}', [Teacher\CourseController::class, 'show'])->name('courses.show');
    Route::patch('/courses/{id}', [Teacher\CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [Teacher\CourseController::class, 'destroy'])->name('courses.destroy');

    // Units
    Route::get('/courses/{courseId}/units/create', [Teacher\UnitController::class, 'create'])->name('units.create');
    Route::post('/courses/{courseId}/units', [Teacher\UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{id}', [Teacher\UnitController::class, 'show'])->name('units.show');
    Route::patch('/units/{id}', [Teacher\UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{id}', [Teacher\UnitController::class, 'destroy'])->name('units.destroy');
    Route::post('/courses/{courseId}/units/reorder', [Teacher\UnitController::class, 'reorder'])->name('units.reorder');

    // Tokens
    Route::get('/tokens', [Teacher\TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [Teacher\TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{id}', [Teacher\TokenController::class, 'destroy'])->name('tokens.destroy');

    // Students
    Route::get('/students', [Teacher\StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{id}', [Teacher\StudentController::class, 'show'])->name('students.show');

    // Course Groups — no dedicated index, handled on dashboard or courses page
    Route::post('/groups', [Teacher\CourseGroupController::class, 'store'])->name('groups.store');
    Route::patch('/groups/{id}', [Teacher\CourseGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{id}', [Teacher\CourseGroupController::class, 'destroy'])->name('groups.destroy');
});

// Student
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/enroll', [Student\EnrollmentController::class, 'store'])->name('enroll');
    Route::get('/courses', [Student\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [Student\CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{courseId}/units/{unitId}', [Student\CourseController::class, 'showUnit'])->name('units.show');
});

// Settings — shared across all roles
Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/avatar', [SettingsController::class, 'updateAvatar'])->name('avatar.update');
    Route::delete('/avatar', [SettingsController::class, 'removeAvatar'])->name('avatar.destroy');
});

// Files — auth only, policy handles role-level access
Route::middleware('auth')->group(function () {
    Route::post('/files', [FileController::class, 'store'])->name('files.store');
    Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/files/{id}/view-token', [FileController::class, 'viewToken'])->name('files.viewToken');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
});

// Raw file stream — signed URL only, no session auth (hit by the browser or external viewers)
Route::get('/files/{id}/raw', [FileController::class, 'raw'])
    ->middleware('signed')
    ->name('files.raw');