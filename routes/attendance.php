<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;


// 勤務表の表示と処理
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
Route::put('/attendance/{attendanceRequest}', [AttendanceController::class, 'update'])->name('attendance.update');
Route::delete('/attendance/{attendanceRequest}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
Route::post('/attendance/submit', [AttendanceController::class, 'submit'])->name('attendance.submit');
Route::post('/attendance/cancel', [AttendanceController::class, 'cancel'])->name('attendance.cancel');
Route::post('/attendance/check-late', [AttendanceController::class, 'checkLateStatus'])->name('attendance.check-late');

Route::post('/attendance', [AttendanceController::class, 'store'])
    ->middleware(\App\Http\Middleware\SyncAttendanceRequestTime::class)
    ->name('attendance.store');