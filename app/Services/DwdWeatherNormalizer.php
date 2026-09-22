<?php

namespace App\Services;

use App\Models\Realm;

/**
 * Converts a DWD stationOverviewExtended response into the OpenWeatherMap
 * forecast shape (WeatherData in resources/js/types.d.ts), so the existing
 * WeatherDataUpdated event, broadcast channel, and frontend rendering need
 * no changes regardless of which provider a realm uses.
 */
class DwdWeatherNormalizer
{
    public function __construct(private DwdStationCatalog $stationCatalog) {}

    /**
     * DWD icon code (1-31) => [OpenWeatherMap base icon, description per locale].
     * Codes and meanings per https://listed.to/@DieSieben/7851/api-des-deutschen-wetterdienstes
     * OWM base icons map to files in public/img/amcharts_weather_icons/{static,animated}/.
     */
    private const ICONS = [
        1 => ['owm' => '01', 'de' => 'Klar', 'en' => 'Clear sky', 'it' => 'Sereno'],
        2 => ['owm' => '02', 'de' => 'Leicht bewölkt', 'en' => 'Partly cloudy', 'it' => 'Poco nuvoloso'],
        3 => ['owm' => '03', 'de' => 'Stark bewölkt', 'en' => 'Mostly cloudy', 'it' => 'Molto nuvoloso'],
        4 => ['owm' => '04', 'de' => 'Bedeckt', 'en' => 'Overcast', 'it' => 'Coperto'],
        5 => ['owm' => '50', 'de' => 'Nebel', 'en' => 'Fog', 'it' => 'Nebbia'],
        6 => ['owm' => '50', 'de' => 'Nebel mit Glätte', 'en' => 'Fog with slippery conditions', 'it' => 'Nebbia con gelo'],
        7 => ['owm' => '10', 'de' => 'Leichter Regen', 'en' => 'Light rain', 'it' => 'Pioggia leggera'],
        8 => ['owm' => '10', 'de' => 'Regen', 'en' => 'Rain', 'it' => 'Pioggia'],
        9 => ['owm' => '09', 'de' => 'Starker Regen', 'en' => 'Heavy rain', 'it' => 'Pioggia forte'],
        10 => ['owm' => '10', 'de' => 'Leichter Regen mit Glätte', 'en' => 'Light rain with slippery conditions', 'it' => 'Pioggia leggera con gelo'],
        11 => ['owm' => '09', 'de' => 'Starker Regen mit Glätte', 'en' => 'Heavy rain with slippery conditions', 'it' => 'Pioggia forte con gelo'],
        12 => ['owm' => '13', 'de' => 'Regen mit etwas Schnee', 'en' => 'Rain with occasional snow', 'it' => 'Pioggia mista a neve'],
        13 => ['owm' => '13', 'de' => 'Regen mit mehr Schnee', 'en' => 'Rain with increased snowfall', 'it' => 'Pioggia mista a molta neve'],
        14 => ['owm' => '13', 'de' => 'Leichter Schnee', 'en' => 'Light snow', 'it' => 'Neve leggera'],
        15 => ['owm' => '13', 'de' => 'Schnee', 'en' => 'Snow', 'it' => 'Neve'],
        16 => ['owm' => '13', 'de' => 'Starker Schnee', 'en' => 'Heavy snow', 'it' => 'Neve forte'],
        17 => ['owm' => '13', 'de' => 'Hagel', 'en' => 'Hail', 'it' => 'Grandine'],
        18 => ['owm' => '10', 'de' => 'Sonnig mit leichtem Regen', 'en' => 'Sunny with light rain', 'it' => 'Soleggiato con pioggia leggera'],
        19 => ['owm' => '09', 'de' => 'Sonnig mit starkem Regen', 'en' => 'Sunny with heavy rain', 'it' => 'Soleggiato con pioggia forte'],
        20 => ['owm' => '13', 'de' => 'Sonnig mit Regen und etwas Schnee', 'en' => 'Sunny with rain and occasional snow', 'it' => 'Soleggiato con pioggia e neve'],
        21 => ['owm' => '13', 'de' => 'Sonnig mit Regen und mehr Schnee', 'en' => 'Sunny with rain and increased snow', 'it' => 'Soleggiato con pioggia e molta neve'],
        22 => ['owm' => '13', 'de' => 'Sonnig mit etwas Schnee', 'en' => 'Sunny with occasional snow', 'it' => 'Soleggiato con neve leggera'],
        23 => ['owm' => '13', 'de' => 'Sonnig mit mehr Schnee', 'en' => 'Sunny with increased snow', 'it' => 'Soleggiato con molta neve'],
        24 => ['owm' => '13', 'de' => 'Sonnig mit Hagel', 'en' => 'Sunny with hail', 'it' => 'Soleggiato con grandine'],
        25 => ['owm' => '13', 'de' => 'Sonnig mit starkem Hagel', 'en' => 'Sunny with heavy hail', 'it' => 'Soleggiato con grandine forte'],
        26 => ['owm' => '11', 'de' => 'Gewitter', 'en' => 'Thunderstorm', 'it' => 'Temporale'],
        27 => ['owm' => '11', 'de' => 'Gewitter mit Regen', 'en' => 'Thunderstorm with rain', 'it' => 'Temporale con pioggia'],
        28 => ['owm' => '11', 'de' => 'Gewitter mit starkem Regen', 'en' => 'Thunderstorm with heavy rain', 'it' => 'Temporale con pioggia forte'],
        29 => ['owm' => '11', 'de' => 'Gewitter mit Hagel', 'en' => 'Thunderstorm with hail', 'it' => 'Temporale con grandine'],
        30 => ['owm' => '11', 'de' => 'Gewitter mit starkem Hagel', 'en' => 'Thunderstorm with heavy hail', 'it' => 'Temporale con grandine forte'],
        31 => ['owm' => 'unknown', 'de' => 'Wind', 'en' => 'Wind', 'it' => 'Vento'],
    ];

    private const MAX_ENTRIES = 6;

    private const MAX_DAILY_ENTRIES = 7;

    /**
     * @param  array  $stationOverview  Decoded stationOverviewExtended response for one station
     *                                  (i.e. $rawResponse[$stationId]).
     */
    public function normalize(array $stationOverview, Realm $realm, string $locale): array
    {
        $forecast = $stationOverview['forecast1'] ?? [];
        $start = (int) ($forecast['start'] ?? 0);
        $timeStep = (int) ($forecast['timeStep'] ?? 3600000);
        $temperatures = $forecast['temperature'] ?? [];
        $icons = $forecast['icon'] ?? [];

        // $forecast['start'] is DWD's own fixed reference point for the
        // array (not necessarily "now"), so index 0 always used to mean
        // "start of the array" rather than "the upcoming hour" - showing
        // hours that had already passed whenever "now" wasn't exactly at
        // that reference point (e.g. always showing 00:00 onward).
        $nowMs = time() * 1000;
        $startIndex = $timeStep > 0 ? max(0, (int) floor(($nowMs - $start) / $timeStep)) : 0;

        $available = min(count($temperatures), count($icons));
        $count = min(self::MAX_ENTRIES, max(0, $available - $startIndex));
        $list = [];

        for ($j = 0; $j < $count; $j++) {
            $i = $startIndex + $j;
            $dt = (int) (($start + $i * $timeStep) / 1000);
            $temp = $temperatures[$i] !== null ? round($temperatures[$i] / 10, 1) : null;
            $icon = $this->resolveIcon((int) $icons[$i], $dt, $realm);
            $description = self::ICONS[(int) $icons[$i]][$locale] ?? self::ICONS[(int) $icons[$i]]['en'] ?? 'unknown';

            $list[] = [
                'dt' => $dt,
                'main' => [
                    'temp' => $temp,
                    // DWD doesn't provide a perceived temperature.
                    'feels_like' => null,
                ],
                'weather' => [[
                    'id' => (int) $icons[$i],
                    'main' => $description,
                    'description' => $description,
                    'icon' => $icon,
                ]],
                // DWD doesn't reliably provide hourly cloud cover either;
                // left at 0 rather than showing misleading data.
                'clouds' => ['all' => 0],
            ];
        }

        $sun = date_sun_info(time(), (float) $realm->lat, (float) $realm->lon);

        return [
            'cod' => '200',
            'message' => 0,
            'cnt' => count($list),
            'list' => $list,
            // Multi-day outlook; DWD delivers ready-made daily aggregates
            // (min/max temperature, total sunshine), unlike the hourly data.
            'daily' => $this->normalizeDaily($stationOverview['days'] ?? [], $locale),
            'city' => [
                'name' => $this->resolveLocationName($realm),
                'sunrise' => $sun['sunrise'],
                'sunset' => $sun['sunset'],
            ],
        ];
    }

    /**
     * @param  array  $days  The stationOverviewExtended response's "days" list.
     */
    private function normalizeDaily(array $days, string $locale): array
    {
        $count = min(self::MAX_DAILY_ENTRIES, count($days));
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $day = $days[$i];
            $code = (int) ($day['icon'] ?? 0);
            $base = self::ICONS[$code]['owm'] ?? null;
            $description = self::ICONS[$code][$locale] ?? self::ICONS[$code]['en'] ?? 'unknown';

            $result[] = [
                'date' => $day['dayDate'] ?? null,
                'temp_min' => isset($day['temperatureMin']) ? round($day['temperatureMin'] / 10, 1) : null,
                'temp_max' => isset($day['temperatureMax']) ? round($day['temperatureMax'] / 10, 1) : null,
                // Total sunshine for the day, in minutes (see normalize()).
                'sunshine' => isset($day['sunshine']) ? (int) round($day['sunshine'] / 10) : null,
                'weather' => [[
                    'id' => $code,
                    'main' => $description,
                    'description' => $description,
                    // Daytime icon variant; a daily summary has no meaningful night state.
                    'icon' => $base === null || $base === 'unknown' ? 'unknown' : $base.'d',
                ]],
            ];
        }

        return $result;
    }

    /**
     * The name of the DWD station nearest the realm's coordinates (e.g.
     * "München Stadt"), shown instead of the realm's own name when
     * available. The catalogue only has names in all caps with umlauts
     * spelled out (UE/OE/AE), which are restored here.
     */
    private function resolveLocationName(Realm $realm): string
    {
        if (blank($realm->lat) || blank($realm->lon)) {
            return $realm->name;
        }

        try {
            $station = $this->stationCatalog->findNearest((float) $realm->lat, (float) $realm->lon);
        } catch (\Throwable) {
            return $realm->name;
        }

        if (! isset($station['name'])) {
            return $realm->name;
        }

        $name = preg_replace(['/UE/', '/OE/', '/AE/'], ['Ü', 'Ö', 'Ä'], $station['name']);

        return mb_convert_case($name, MB_CASE_TITLE);
    }

    private function resolveIcon(int $code, int $dt, Realm $realm): string
    {
        $base = self::ICONS[$code]['owm'] ?? null;

        if ($base === null || $base === 'unknown') {
            return 'unknown';
        }

        $sun = date_sun_info($dt, (float) $realm->lat, (float) $realm->lon);
        $isDay = $dt >= $sun['sunrise'] && $dt < $sun['sunset'];

        return $base.($isDay ? 'd' : 'n');
    }
}
