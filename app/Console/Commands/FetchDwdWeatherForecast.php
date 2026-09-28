<?php

namespace App\Console\Commands;

use App\Models\Realm;
use App\Providers\WeatherDataUpdated;
use App\Services\DwdWeatherNormalizer;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchDwdWeatherForecast extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetchDwd
    {--S|station_id= : the DWD station ID ("Stationskennung"), see https://www.dwd.de/DE/leistungen/klimadatendeutschland/stationsliste.html}
    {--R|realm= : the realm ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the weather data from the Deutscher Wetterdienst (DWD) API and stores it to a json file';

    /**
     * Execute the console command.
     */
    public function handle(DwdWeatherNormalizer $normalizer): void
    {
        $realmID = $this->option('realm');
        if (! $realmID) {
            $this->error('No realm ID provided');

            return;
        }

        $stationId = $this->option('station_id');
        if (! $stationId) {
            $this->error('No station ID provided.');

            return;
        }

        $realm = Realm::find($realmID);
        if (! $realm) {
            $this->error("Realm {$realmID} not found.");

            return;
        }

        $url = "https://app-prod-ws.warnwetter.de/v30/stationOverviewExtended?stationIds={$stationId}";
        $this->info("Pulling weather data from {$url} …");
        $response = Http::withoutVerifying()->retry(3, 10000)->get($url);
        $response->throw();

        if ($response->successful()) {
            $stationOverview = $response->json((string) $stationId);

            if (! $stationOverview) {
                $this->error("No data returned for station {$stationId}.");

                return;
            }

            $normalized = $normalizer->normalize($stationOverview, $realm, config('app.locale', 'en'));

            if (! Storage::disk('local')->put("weather-dwd-{$stationId}.json", json_encode($normalized))) {
                $this->error('Could not write weather data to '.Storage::disk('local')->path("weather-dwd-{$stationId}.json"));
            } else {
                event(new WeatherDataUpdated($normalized, $realmID));
            }
        }
    }
}
