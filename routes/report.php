<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftApprovalController;
use App\Http\Controllers\WorkingCorrectionApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('report.index');
    Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('report.attendance');

    Route::get('/reports/shift-approvals', [ShiftApprovalController::class, 'index'])->name('shift.approvals.index');
    Route::post('/reports/shift-approvals/{shiftSubmission}/approve', [ShiftApprovalController::class, 'approve'])->name('shift.approvals.approve');
    Route::post('/reports/shift-approvals/{shiftSubmission}/withdraw', [ShiftApprovalController::class, 'withdraw'])->name('shift.approvals.withdraw');

    // 💡 勤怠申請（打刻修正）承認
    Route::get('/reports/working-corrections', [WorkingCorrectionApprovalController::class, 'index'])->name('working.corrections.index');
    Route::post('/reports/working-corrections/{workingCorrection}/approve', [WorkingCorrectionApprovalController::class, 'approve'])->name('working.corrections.approve');
    Route::post('/reports/working-corrections/{workingCorrection}/reject', [WorkingCorrectionApprovalController::class, 'reject'])->name('working.corrections.reject');
});