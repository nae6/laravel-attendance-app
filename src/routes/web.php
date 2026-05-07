<?php

use App\Http\Controllers\AttendanceActionController;
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
Route::get('/attendance', [AttendanceActionController::class, 'edit'])
    ->name('attendance');
Route::post('/attendance/start', [AttendanceActionController::class, 'startWork'])
    ->name('attendance.start');
Route::post('/attendance/break-start', [AttendanceActionController::class, 'startBreak'])
    ->name('break.start');
Route::post('/attendance/break-end', [AttendanceActionController::class, 'endBreak'])
    ->name('break.end');
Route::post('/attendance/end', [AttendanceActionController::class, 'endWork'])
    ->name('attendance.end');

Route::get('/attendance/list', [AttendanceController::class, 'index'])
    ->name('attendance.index');
Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'edit'])
    ->name('attendance.edit');
Route::put('/attendance/detail/{attendance}', [AttendanceController::class, 'update'])
    ->name('attendance.update');

// 入力が無い日の詳細表示・新規登録が必要な場合
Route::get('/attendance/detail/date/{date}', [AttendanceController::class, 'create'])
    ->name('attendance.create');
Route::post('/attendance/detail/date/{date}', [AttendanceController::class, 'store'])
    ->name('attendance.store');


// adminの画面