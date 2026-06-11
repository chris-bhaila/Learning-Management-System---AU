<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\Student;
use App\Http\Controllers\FileController;

// Landing page
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->role->name . '.dashboard');
    }
    return view('landing');
})->name('home');

// Auth
// Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
// Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
// Route::post('/logout', function () {
//     Auth::logout();
//     request()->session()->invalidate();
//     request()->session()->regenerateToken();
//     return redirect()->route('home');
// })->middleware('auth')->name('logout');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{id}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Courses — no show/edit routes, handled by modals
    Route::get('/courses', [Admin\CourseController::class, 'index'])->name('courses.index');
    Route::patch('/courses/{id}', [Admin\CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [Admin\CourseController::class, 'destroy'])->name('courses.destroy');

    // Units — no show/edit routes, handled by modals
    Route::patch('/units/{id}', [Admin\UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{id}', [Admin\UnitController::class, 'destroy'])->name('units.destroy');

    // Tokens
    Route::get('/tokens', [Admin\TokenController::class, 'index'])->name('tokens.index');
    Route::delete('/tokens/{id}', [Admin\TokenController::class, 'destroy'])->name('tokens.destroy');

    // Course Groups
    Route::get('/groups', [Admin\CourseGroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [Admin\CourseGroupController::class, 'store'])->name('groups.store');
    Route::patch('/groups/{id}', [Admin\CourseGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{id}', [Admin\CourseGroupController::class, 'destroy'])->name('groups.destroy');
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

    // Units — no edit route, handled by modal
    Route::get('/courses/{courseId}/units/create', [Teacher\UnitController::class, 'create'])->name('units.create');
    Route::post('/courses/{courseId}/units', [Teacher\UnitController::class, 'store'])->name('units.store');
    Route::patch('/units/{id}', [Teacher\UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{id}', [Teacher\UnitController::class, 'destroy'])->name('units.destroy');
    Route::post('/courses/{courseId}/units/reorder', [Teacher\UnitController::class, 'reorder'])->name('units.reorder');

    // Tokens
    Route::post('/tokens', [Teacher\TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{id}', [Teacher\TokenController::class, 'destroy'])->name('tokens.destroy');

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

// Files — auth only, policy handles role-level access
Route::middleware('auth')->group(function () {
    Route::post('/files', [FileController::class, 'store'])->name('files.store');
    Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
});