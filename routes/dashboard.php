<?php

use App\Http\Controllers\WorkingCorrectionController;
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
    
//打刻修正申請
Route::post('/dashboard/correct', [WorkingCorrectionController::class, 'updateCorrection'])->name('clock.correct');
//休憩開始
Route::post('/dashboard/break-in', [DashboardController::class, 'breakIn'])->name('dashboard.breakIn');
// トグルスイッチ
Route::post('/dashboard/toggle-auto-break', [DashboardController::class, 'toggleAutoBreak'])->name('user.toggle_auto_break');
//修正申請のキャンセル
Route::post('/dashboard/correction/{id}/cancel', [WorkingCorrectionController::class, 'cancelCorrection'])->name('clock.correction.cancel');