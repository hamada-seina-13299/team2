<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceCorrectionController;

Route::get('/', function () {
    return view('welcome');
});
// 勤怠修正画面の表示と処理
Route::get('/attendance/correction', [AttendanceCorrectionController::class, 'index'])->name('attendancecorrection.index');
Route::post('/attendance/correction', [AttendanceCorrectionController::class, 'store'])->name('attendancecorrection.store');
Route::delete('/attendance/correction/{shift}', [AttendanceCorrectionController::class, 'destroy'])->name('attendancecorrection.destroy');