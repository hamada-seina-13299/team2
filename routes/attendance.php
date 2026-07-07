<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

// 勤務表の表示と処理
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
Route::put('/attendance/{attendanceRequest}', [AttendanceController::class, 'update'])->name('attendance.update');
Route::delete('/attendance/{attendanceRequest}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
Route::post('/attendance/submit', [AttendanceController::class, 'submit'])->name('attendance.submit');
Route::post('/attendance/cancel', [AttendanceController::class, 'cancel'])->name('attendance.cancel');