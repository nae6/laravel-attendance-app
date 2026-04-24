<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'user'])->name('login');
    Route::get('/admin/login', [LoginController::class, 'admin'])->name('admin.login');
});

Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');