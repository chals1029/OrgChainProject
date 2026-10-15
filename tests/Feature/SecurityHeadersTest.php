<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_https_production_responses_prevent_cross_origin_framing_and_send_hsts(): void
    {
        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_untrusted_forwarded_https_does_not_enable_hsts_on_http(): void
    {
        $this->app['env'] = 'production';
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_protected_redirects_are_not_cacheable_and_same_origin_previews_are_permitted(): void
    {
        $response = $this->get('/office-desk')->assertRedirect();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
    }
}
