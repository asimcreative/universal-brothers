<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Defense-in-depth against the raw-HTML CMS fields this app
        // intentionally renders unescaped (Page::body, NewsArticle::body,
        // Office::google_maps_embed — all admin-authored, see the
        // release-gate security audit). `script-src`/`style-src` still allow
        // 'unsafe-inline' — the app genuinely uses inline `style=""`
        // attributes and one inline `<script>` block (the Hajj detail
        // currency switcher) that would otherwise break; eliminating those
        // is a real refactor, not done in this pass, and is disclosed as a
        // known limitation rather than silently working around it. Even
        // with that allowance, this still blocks loading a script/style/
        // image/font from an attacker-controlled external origin, blocks
        // <object>/<embed> entirely, and restricts framing/form-submission/
        // base URI to this site — real value against exfiltration-style
        // payloads even though it doesn't fully neutralize inline-script XSS.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "frame-src 'self' https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]));

        return $response;
    }
}
