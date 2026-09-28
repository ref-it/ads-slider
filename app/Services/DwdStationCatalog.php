<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fetches and parses the DWD MOSMIX station catalogue (station ID, name,
 * lat/lon), so a realm's DWD station ID can be looked up from its lat/lon
 * instead of requiring the admin to search the DWD website manually.
 */
class DwdStationCatalog
{
    private const CATALOG_URL = 'https://www.dwd.de/DE/leistungen/met_verfahren_mosmix/mosmix_stationskatalog.cfg?view=nasPublication&nn=16102';

    private const CACHE_KEY = 'dwd_station_catalog';

    private const CACHE_TTL_SECONDS = 86400;

    /**
     * @return array<int, array{id: string, name: string, lat: float, lon: float}>
     */
    public function stations(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $response = Http::get(self::CATALOG_URL);
            $response->throw();

            return $this->parse($response->body());
        });
    }

    /**
     * @return array{id: string, name: string, lat: float, lon: float, distance_km: float}|null
     */
    public function findNearest(float $lat, float $lon): ?array
    {
        $nearest = null;
        $nearestDistance = null;

        foreach ($this->stations() as $station) {
            $distance = $this->haversineDistanceKm($lat, $lon, $station['lat'], $station['lon']);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = $station;
            }
        }

        if (! $nearest) {
            return null;
        }

        return [...$nearest, 'distance_km' => round($nearestDistance, 1)];
    }

    /**
     * @return array<int, array{id: string, name: string, lat: float, lon: float}>
     */
    private function parse(string $body): array
    {
        $stations = [];

        foreach (explode("\n", $body) as $line) {
            $tokens = preg_split('/\s+/', trim($line));

            if (count($tokens) < 5 || ! is_numeric($tokens[count($tokens) - 1])) {
                continue;
            }

            $elevation = array_pop($tokens);
            $lon = array_pop($tokens);
            $lat = array_pop($tokens);

            if (! is_numeric($lat) || ! is_numeric($lon) || ! is_numeric($elevation)) {
                continue;
            }

            $id = array_shift($tokens);
            array_shift($tokens); // ICAO code, unused
            $name = implode(' ', $tokens);

            if ($id === '' || $name === '') {
                continue;
            }

            $stations[] = [
                'id' => $id,
                'name' => $name,
                'lat' => $this->degreesMinutesToDecimal((float) $lat),
                'lon' => $this->degreesMinutesToDecimal((float) $lon),
            ];
        }

        return $stations;
    }

    /**
     * The catalogue expresses coordinates as DDD.MM (degrees, then minutes
     * as a two-digit fraction), not decimal degrees, e.g. 48.10 = 48°10' = 48.1667°.
     */
    private function degreesMinutesToDecimal(float $value): float
    {
        $sign = $value < 0 ? -1 : 1;
        $absolute = abs($value);
        $degrees = floor($absolute);
        $minutes = round(($absolute - $degrees) * 100, 4);

        return $sign * ($degrees + $minutes / 60);
    }

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
