<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route; // 【追記】Routeファサードを使えるようにします

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/login.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // shiftcorrection.php を追加で読み込む
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/shiftcorrection.php'));
        },

        // 【追記】ここから
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/dashboard.php'));
        },
        // 【追記】ここまで
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();