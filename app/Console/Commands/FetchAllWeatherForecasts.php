<?php

namespace App\Console\Commands;

use App\Models\Realm;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Artisan;

class FetchAllWeatherForecasts extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetchAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the weather data from the openweatherapi server and stores them to a json file, for all realms';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Realm::all()->each(function ($realm) {
            if (! $realm->hasWeatherProviderConfigured()) {
                $this->warn("Realm {$realm->name}: no weather provider configured.");

                return;
            }

            if ($realm->effectiveWeatherProvider() === 'dwd') {
                Artisan::call('weather:fetchDwd', ['--realm' => $realm->id, '--station_id' => $realm->dwd_station_id]);

                return;
            }

            Artisan::call('weather:fetch', ['--realm' => $realm->id, '--city_id' => $realm->ow_city_id, '--api_key' => $realm->ow_api_key]);
        });
    }
}
