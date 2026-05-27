<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminAttendanceCorrectController;
use App\Http\Controllers\AttendanceActionController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\CorrectRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\LoginController;

/**
 * メール認証用の設定
 */
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/**
 * roleごとのログイン画面表示切り替え
 */
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'user'])
        ->name('login');
    Route::get('/admin/login', [LoginController::class, 'admin'])
        ->name('admin.login');
});

/**
 * ログイン済一般ユーザー
 */
Route::middleware(['auth', 'verified'])->group(function () {
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

    Route::put('/attendance/detail/{attendance}', [CorrectRequestController::class, 'update'])
        ->name('attendance.update');
    Route::get('/stamp_correction_request/list', [CorrectRequestController::class, 'index'])
        ->name('request.list');
});

/**
 * 管理者のみ
 */
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');
    Route::get('/admin/attendance/{attendance}', [AdminAttendanceController::class, 'edit'])
        ->name('admin.attendance.edit');

    Route::put('/admin/attendance/detail/{attendance}', [AdminAttendanceCorrectController::class, 'update'])
        ->name('admin.attendance.update');
    Route::get('/admin/stamp_correction_request/list', [AdminAttendanceCorrectController::class, 'index'])
        ->name('admin.request.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceCorrectController::class, 'show'])
        ->name('admin.request.show');
    Route::put('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceCorrectController::class, 'approve'])
        ->name('admin.request.approve');

    Route::get('/admin/staff/list', [AdminStaffController::class, 'staffList'])
        ->name('staff.list');
    Route::get('/admin/attendance/staff/{staff}', [AdminStaffController::class, 'attendanceHistory'])
        ->name('staff.attendance.list');
    Route::get('/admin/attendance/staff/{staff}/export', [AdminStaffController::class, 'export'])
        ->name('staff.attendance.export');
});