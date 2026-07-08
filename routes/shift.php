<?php

use App\Http\Controllers\ShiftController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/dev-login', function () {
    $user = User::find(1);
    Auth::login($user);
    return redirect('/shift/list');
});

Route::middleware('auth')->group(function () {
    Route::get('/shift/list', [ShiftController::class, 'index'])->name('shift.list');
    Route::delete('/shift/delete', [ShiftController::class, 'destroy'])->name('shift.delete');
    Route::post('/shift/add', [ShiftController::class, 'store'])->name('shift.store');
    Route::post('/shift/update-time', [ShiftController::class, 'updateTime'])->name('shift.updateTime');
    Route::delete('/shift/master/delete', [ShiftController::class, 'destroyMaster'])->name('shift.master.delete');
    Route::post('/shift/master/clear', [ShiftController::class, 'clearLastMaster'])->name('shift.master.clear');
    Route::post('/shift/submit', [ShiftController::class, 'submit'])->name('shift.submit');
    Route::post('/shift/withdraw', [ShiftController::class, 'withdraw'])->name('shift.withdraw');
    Route::post('/shift/master/clear', [ShiftController::class, 'clearLastMaster'])->name('shift.master.clear');
});