<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_baseline_security_headers(): void
    {
        Office::factory()->create();

        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    /**
     * Regression for a MEDIUM finding from the release-gate security audit:
     * no Content-Security-Policy existed at all, despite this app
     * intentionally rendering admin-authored raw HTML on public pages
     * (Page::body, NewsArticle::body, Office::google_maps_embed).
     */
    public function test_responses_include_a_content_security_policy(): void
    {
        Office::factory()->create();

        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }
}
