<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftCorrectionController;

Route::get('/', function () {
    return view('welcome');
});
// シフト修正画面の表示と処理
Route::get('/shift/correction', [ShiftCorrectionController::class, 'index'])->name('shiftcorrection.index');
Route::post('/shift/correction', [ShiftCorrectionController::class, 'store'])->name('shiftcorrection.store');