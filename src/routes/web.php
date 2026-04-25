<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// roleごとのlogin画面の表示
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'user'])
        ->name('login');
    Route::get('/admin/login', [LoginController::class, 'admin'])
        ->name('admin.login');
});

// userの画面
Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance');
Route::post('/attendance', [AttendanceController::class, 'startWork'])
    ->name('attendance.start');
Route::post('/attendance', [AttendanceController::class, 'endWork'])
    ->name('attendance.end');

// adminの画面