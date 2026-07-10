<?php

use App\Http\Controllers\AttendanceRequestApprovalController;
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

    // 💡 打刻修正申請の承認
    Route::get('/reports/working-corrections', [WorkingCorrectionApprovalController::class, 'index'])->name('working.corrections.index');
    Route::post('/reports/working-corrections/{workingCorrection}/approve', [WorkingCorrectionApprovalController::class, 'approve'])->name('working.corrections.approve');
    Route::post('/reports/working-corrections/{workingCorrection}/reject', [WorkingCorrectionApprovalController::class, 'reject'])->name('working.corrections.reject');

    // 💡 勤怠申請（遅刻・早退・欠勤・有給・半休・残業・有事遅刻・有事早退）の承認
    Route::get('/reports/attendance-requests', [AttendanceRequestApprovalController::class, 'index'])->name('attendance.approvals.index');
    Route::post('/reports/attendance-requests/{attendanceRequest}/approve', [AttendanceRequestApprovalController::class, 'approve'])->name('attendance.approvals.approve');
    Route::post('/reports/attendance-requests/{attendanceRequest}/reject', [AttendanceRequestApprovalController::class, 'reject'])->name('attendance.approvals.reject');
    Route::post('/reports/attendance-requests/{attendanceRequest}/undo', [AttendanceRequestApprovalController::class, 'undo'])->name('attendance.approvals.undo');
});