<?php

namespace Tests\Feature;

use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorCspTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_header_is_set_on_the_kiosk_display_route(): void
    {
        $monitor = Monitor::factory()->create();

        $response = $this->get(route('showEventsToken', $monitor->api_token));

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString('style-src-attr \'unsafe-inline\'', $csp);
        $this->assertStringContainsString('img-src \'self\' data: https://openweathermap.org', $csp);
        $this->assertStringContainsString('worker-src \'self\' blob:', $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_csp_header_is_omitted_when_disabled_via_env(): void
    {
        config(['csp.enabled' => false]);

        $monitor = Monitor::factory()->create();

        $response = $this->get(route('showEventsToken', $monitor->api_token));

        $response->assertOk();
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_inline_script_nonce_matches_the_csp_header(): void
    {
        $monitor = Monitor::factory()->create();

        $response = $this->get(route('showEventsToken', $monitor->api_token));

        $csp = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $response->assertSee('<script nonce="'.$matches[1].'">', false);
    }
}
