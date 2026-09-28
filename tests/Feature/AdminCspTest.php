<?php

namespace Tests\Feature;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCspTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_header_is_set_on_admin_pages_with_unsafe_eval(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create(['realm_id' => $realm->id]);

        $response = $this->actingAs($user)->get(route('monitors.index'));

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-eval' 'nonce-", $csp);
        $this->assertStringNotContainsString('openweathermap.org', $csp);
    }

    public function test_csp_header_is_omitted_when_disabled_via_env(): void
    {
        config(['csp.enabled' => false]);

        $realm = Realm::factory()->create();
        $user = User::factory()->create(['realm_id' => $realm->id]);

        $response = $this->actingAs($user)->get(route('monitors.index'));

        $response->assertOk();
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_monitor_preview_route_gets_monitor_preset_not_admin_preset(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create(['realm_id' => $realm->id]);
        $monitor = \App\Models\Monitor::factory()->create(['realm_id' => $realm->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('showEvents', $monitor));

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString('worker-src \'self\' blob:', $csp);
    }
}
