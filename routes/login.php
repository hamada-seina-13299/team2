<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// ログイン画面の表示
Route::get('/login', [LoginController::class, 'index'])->name('login');
// ログイン処理
Route::post('/login', [LoginController::class, 'login']);

// ログアウト処理
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// 1. パスワードをお忘れの方はこちら（画面表示と送信処理）
Route::get('/password/passwordRequest', [LoginController::class, 'showRequestForm'])->name('password.passwordRequest');
Route::post('/password/passwordRequest', [LoginController::class, 'sendResetLink']);

// 2. メールのURLをクリックした先（画面表示と確定処理）
Route::get('/password/passwordReset/{token}', [LoginController::class, 'showResetForm'])->name('password.passwordReset');
Route::post('/password/passwordReset', [LoginController::class, 'resetPassword'])->name('password.update');