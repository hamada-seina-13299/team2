<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// ログイン画面の表示
Route::get('/login', [LoginController::class, 'index'])->name('login');
// ログイン処理
Route::post('/login', [LoginController::class, 'login']);

// ログアウト処理
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
