<?php

namespace Tests\Feature;

use App\Services\DwdStationCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DwdStationCatalogTest extends TestCase
{
    private const CATALOG_BODY = "ID    ICAO NAME                 LAT    LON     ELEV\n".
        "----- ---- -------------------- -----  ------- -----\n".
        "01001 ENJA JAN MAYEN             70.56   -8.40    10\n".
        "10865 ---- MUENCHEN STADT        48.10   11.32   515\n".
        "10315 EDDG MUENSTER/OSNABR.      52.08    7.42    48\n";

    protected function setUp(): void
    {
        parent::setUp();

        // The "array" cache store is process-wide and outlives RefreshDatabase,
        // so the catalogue cached by one test would otherwise leak into the next.
        Cache::flush();
    }

    public function test_it_finds_the_nearest_station_to_given_coordinates(): void
    {
        Http::fake([
            'https://www.dwd.de/*' => Http::response(self::CATALOG_BODY, 200),
        ]);

        $station = (new DwdStationCatalog)->findNearest(48.14, 11.58);

        $this->assertSame('10865', $station['id']);
        $this->assertSame('MUENCHEN STADT', $station['name']);
        $this->assertEqualsWithDelta(48.1667, $station['lat'], 0.001);
        $this->assertEqualsWithDelta(11.5333, $station['lon'], 0.001);
    }

    public function test_it_caches_the_catalogue_between_lookups(): void
    {
        Http::fake([
            'https://www.dwd.de/*' => Http::response(self::CATALOG_BODY, 200),
        ]);

        $catalog = new DwdStationCatalog;
        $catalog->findNearest(48.14, 11.58);
        $catalog->findNearest(70.9, -8.7);

        Http::assertSentCount(1);
    }

    public function test_it_returns_null_when_the_catalogue_is_empty(): void
    {
        Http::fake([
            'https://www.dwd.de/*' => Http::response('', 200),
        ]);

        $station = (new DwdStationCatalog)->findNearest(48.14, 11.58);

        $this->assertNull($station);
    }
}
