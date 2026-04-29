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
Route::get('/attendance', [AttendanceController::class, 'edit'])
    ->name('attendance');
Route::post('/attendance/start', [AttendanceController::class, 'startWork'])
    ->name('attendance.start');
Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak'])
    ->name('break.start');
Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak'])
    ->name('break.end');
Route::post('/attendance/end', [AttendanceController::class, 'endWork'])
    ->name('attendance.end');
Route::get('/attendance/list', [AttendanceController::class, 'index'])
    ->name('attendance.index');
Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])
    ->name('attendance.show');

// adminの画面