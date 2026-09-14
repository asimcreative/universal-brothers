<?php

namespace App\Http\Middleware;

use App\Support\SiteImagery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clear the "photographs rendered so far" set at the start of every request.
 *
 * SiteImagery tracks which photographs a page emitted so the footer can credit
 * exactly those, and so an empty state can avoid repeating the hero's picture.
 * That set is static, which is correct for the tracking itself but wrong across
 * requests: under PHP-FPM each request gets a fresh process and the problem
 * hides, but the moment two requests share one — the test suite, `artisan
 * serve`, Octane, any persistent runtime — the set keeps growing and a page
 * credits photographs that belong to the page before it.
 *
 * Caught by the test suite rather than in production, because the suite is the
 * one place that already runs many requests in a single process.
 */
class ResetImageryTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        SiteImagery::forgetUsed();

        return $next($request);
    }
}
