<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Admin APIs use Sanctum bearer tokens; stateful SPA cookies are intentionally disabled.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
