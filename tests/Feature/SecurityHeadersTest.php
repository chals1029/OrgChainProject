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

    public function test_status_origin_can_read_only_the_public_check_responses(): void
    {
        foreach (['/', '/up', '/health/services'] as $path) {
            $response = $this->withHeaders(['Origin' => 'https://status.orgchain.tech'])->get($path);

            $response->assertOk()
                ->assertHeader('Access-Control-Allow-Origin', 'https://status.orgchain.tech')
                ->assertHeaderMissing('Access-Control-Allow-Credentials');
            $this->assertStringContainsString('Origin', $response->headers->get('Vary'));
        }

        $this->call('HEAD', '/up', server: ['HTTP_ORIGIN' => 'https://status.orgchain.tech'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://status.orgchain.tech');

        $this->withHeaders(['Origin' => 'https://status.orgchain.tech'])
            ->get('/portal')
            ->assertRedirect()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->withHeaders(['Origin' => 'https://status.orgchain.tech'])
            ->get('/health/services/database')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_status_check_responses_reject_other_origins(): void
    {
        foreach (['https://other.example', 'https://status.orgchain.tech.evil.example'] as $origin) {
            foreach (['/', '/up', '/health/services'] as $path) {
                $response = $this->withHeaders(['Origin' => $origin])->get($path);

                $response->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
                $this->assertStringContainsString('Origin', $response->headers->get('Vary'));
            }
        }
    }
}
