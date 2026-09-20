<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ResetImageryTracking;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Currency;
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

        // Which of the three price lists the visitor reads, remembered for a
        // year. Left in the clear because it is a display preference, not a
        // credential: it holds one of three fixed strings, every read
        // validates it against that list, and forging it only changes the
        // prices the forger themselves sees. Encrypting it would buy nothing
        // and would leave the preference unreadable to anything but PHP.
        $middleware->encryptCookies(except: [Currency::COOKIE]);

        $middleware->append(SecurityHeaders::class);

        // Prepended, not appended: the tracking set has to be empty BEFORE any
        // view renders, and `append` would run it after the response is built.
        $middleware->prepend(ResetImageryTracking::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
