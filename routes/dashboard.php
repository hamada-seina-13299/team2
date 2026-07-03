<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ダッシュボード画面表示
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// 勤怠管理（打刻）グループ
Route::prefix('clock')->name('clock.')->group(function () {
    // 出勤打刻
    Route::post('/in', [DashboardController::class, 'clockIn'])->name('in');
    
    // 退勤打刻
    Route::post('/out', [DashboardController::class, 'clockOut'])->name('out');
});
    
//打刻修正申請用のルート
Route::post('/dashboard/correct', [DashboardController::class, 'updateCorrection'])->name('clock.correct');

Route::post('/dashboard/toggle-auto-break', [DashboardController::class, 'toggleAutoBreak'])->name('user.toggle_auto_break');