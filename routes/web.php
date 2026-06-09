<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landingPage');
})->name('home');

Route::get('/student', function () {
    return view('layouts.student');
})->name('student');