<?php

namespace Tests\Feature;

use App\Livewire\EditMonitor;
use App\Models\Monitor;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_forecast_checkbox_is_disabled_when_realm_has_no_openweather_api_key(): void
    {
        $realm = Realm::factory()->create([
            'ow_api_key' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_forecast', false)
            ->assertSeeHtml('disabled')
            ->assertSee(__('Please set up a weather provider (OpenWeatherMap or DWD) in the'))
            ->assertSee(route('realms.edit', $realm->id));
    }

    public function test_weather_forecast_checkbox_shows_text_hint_for_non_admin_users(): void
    {
        $realm = Realm::factory()->create([
            'ow_api_key' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_forecast', false)
            ->assertSeeHtml('disabled')
            ->assertSee(__('Please set up a weather provider (OpenWeatherMap or DWD) in the settings.'));
    }

    public function test_weather_forecast_checkbox_is_enabled_when_realm_has_openweather_api_key(): void
    {
        $realm = Realm::factory()->create([
            'ow_api_key' => 'valid_api_key_12345',
            'ow_city_id' => '2867714',
        ]);
        $user = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_forecast', true)
            ->assertDontSee(__('Please set up a weather provider (OpenWeatherMap or DWD) in the settings.'));
    }

    public function test_weather_forecast_checkbox_is_enabled_when_realm_has_dwd_station_id(): void
    {
        $realm = Realm::factory()->create([
            'weather_provider' => 'dwd',
            'dwd_station_id' => '10865',
        ]);
        $user = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_forecast', true)
            ->assertDontSee(__('Please set up a weather provider (OpenWeatherMap or DWD) in the settings.'));
    }

    public function test_weather_forecast_checkbox_is_disabled_when_realm_has_dwd_provider_without_station_id(): void
    {
        $realm = Realm::factory()->create([
            'weather_provider' => 'dwd',
            'dwd_station_id' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_forecast', false)
            ->assertSeeHtml('disabled');
    }

    public function test_save_monitor_forces_weather_forecast_to_false_without_api_key(): void
    {
        $realm = Realm::factory()->create([
            'ow_api_key' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->set('form.name', 'Test Monitor')
            ->set('form.show_weather_forecast', true)
            ->call('createMonitor')
            ->assertHasNoErrors()
            ->assertRedirect(route('monitors.index'));

        $monitor = Monitor::where('name', 'Test Monitor')->first();
        $this->assertNotNull($monitor);
        $this->assertFalse((bool) $monitor->show_weather_forecast);
    }

    public function test_daily_weather_forecast_checkbox_is_enabled_only_for_dwd(): void
    {
        $owmRealm = Realm::factory()->create([
            'weather_provider' => 'openweathermap',
            'ow_api_key' => 'valid_api_key_12345',
            'ow_city_id' => '2867714',
        ]);
        $owmUser = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $owmRealm->id,
        ]);

        Livewire::actingAs($owmUser)
            ->test(EditMonitor::class)
            ->assertSet('form.show_weather_daily_forecast', false);

        $dwdRealm = Realm::factory()->create([
            'weather_provider' => 'dwd',
            'dwd_station_id' => '10865',
        ]);
        $dwdUser = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $dwdRealm->id,
        ]);

        Livewire::actingAs($dwdUser)
            ->test(EditMonitor::class)
            ->set('form.show_weather_daily_forecast', true)
            ->assertSet('form.show_weather_daily_forecast', true);
    }

    public function test_save_monitor_forces_daily_weather_forecast_to_false_without_dwd(): void
    {
        $realm = Realm::factory()->create([
            'weather_provider' => 'openweathermap',
            'ow_api_key' => 'valid_api_key_12345',
            'ow_city_id' => '2867714',
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class)
            ->set('form.name', 'Test Monitor Daily')
            ->set('form.show_weather_daily_forecast', true)
            ->call('createMonitor')
            ->assertHasNoErrors()
            ->assertRedirect(route('monitors.index'));

        $monitor = Monitor::where('name', 'Test Monitor Daily')->first();
        $this->assertNotNull($monitor);
        $this->assertFalse((bool) $monitor->show_weather_daily_forecast);
    }
}
