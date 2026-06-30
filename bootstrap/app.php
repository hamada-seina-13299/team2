<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
