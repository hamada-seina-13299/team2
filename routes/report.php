<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('report.index');
    Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('report.attendance');
    Route::get('/reports/shift-approvals', [ShiftApprovalController::class, 'index'])->name('shift.approvals.index');
    Route::post('/reports/shift-approvals/{shiftSubmission}/approve', [ShiftApprovalController::class, 'approve'])->name('shift.approvals.approve');
    Route::post('/reports/shift-approvals/{shiftSubmission}/withdraw', [ShiftApprovalController::class, 'withdraw'])->name('shift.approvals.withdraw');
});