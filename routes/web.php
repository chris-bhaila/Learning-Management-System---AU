<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landingPage');
})->name('home');

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::get('/teacher/dashboard', function () {
    return view('teacher.dashboard');
})->name('teacher.dashboard');

Route::get('/student/dashboard', function () {
    return view('student.dashboard');
})->name('student.dashboard');