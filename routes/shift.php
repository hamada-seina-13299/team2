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

    Route::get('/shift/add', [ShiftController::class, 'create'])->name('shift.add');
    Route::post('/shift/add', [ShiftController::class, 'store'])->name('shift.store');

    // マスタ削除用
    Route::delete('/shift/master/delete', [ShiftController::class, 'destroyMaster'])->name('shift.master.delete');
});