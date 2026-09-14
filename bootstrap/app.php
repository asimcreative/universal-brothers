<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ResetImageryTracking;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo('/admin/login');

        $middleware->append(SecurityHeaders::class);

        // Prepended, not appended: the tracking set has to be empty BEFORE any
        // view renders, and `append` would run it after the response is built.
        $middleware->prepend(ResetImageryTracking::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
