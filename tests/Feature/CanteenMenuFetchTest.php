<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use App\Services\CanteenMenuParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class CanteenMenuFetchTest extends TestCase
{
    use RefreshDatabase;

    private Realm $realm;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Cache::flush();

        // Inside the canteens:fetch opening-hours window regardless of wall-clock time.
        Date::setTestNow(Date::parse('2026-09-22 12:00:00'));

        $this->realm = Realm::factory()->create();
        $this->user = User::factory()->create(['realm_id' => $this->realm->id]);
    }

    private function makeCanteen(array $scheduleOverrides = []): Canteen
    {
        $canteen = Canteen::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'external_id' => 46,
        ]);

        Schedule::factory()->create(array_merge([
            'scheduleable_type' => 'CA',
            'scheduleable_id' => $canteen->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'disabled' => false,
        ], $scheduleOverrides));

        return $canteen;
    }

    public function test_fetches_and_caches_the_menu_for_an_enabled_canteen(): void
    {
        $ics = <<<'HTML'
        <div class="splGroupWrapper">
            <div class="rowMealInner">
                <div class="mealText">Gemüsecurry mit Reis</div>
                <div class="mealPreise">2,20 / 4,40 / 6,60 €</div>
            </div>
        </div>
        HTML;

        Http::fake([
            'https://www.stw-thueringen.de/*' => Http::response($ics, 200),
        ]);

        $canteen = $this->makeCanteen();

        $exitCode = Artisan::call('canteens:fetch');
        $this->assertEquals(0, $exitCode);

        Http::assertSent(function ($request) use ($canteen) {
            return $request['resources_id'] == $canteen->external_id;
        });

        Storage::disk('local')->assertExists("canteen-{$canteen->id}.json");
        $cached = json_decode(Storage::disk('local')->get("canteen-{$canteen->id}.json"), true);
        $this->assertEquals('Gemüsecurry mit Reis', $cached['lunch'][0]['name']);

        $this->assertEquals('Gemüsecurry mit Reis', $canteen->refresh()->menu['lunch'][0]['name']);
    }

    public function test_fetches_and_caches_both_locale_editions_separately(): void
    {
        Http::fake([
            'https://www.stw-thueringen.de/*' => function ($request) {
                $isEnglish = $request['resources_id'] == 597;
                $name = $isEnglish ? 'fried soy steak' : 'Gebratenes Sojasteak';

                return Http::response(<<<HTML
                <div class="splGroupWrapper">
                    <div class="rowMealInner">
                        <div class="mealText">{$name}</div>
                        <div class="mealPreise">2,20 / 4,40 / 6,60 €</div>
                    </div>
                </div>
                HTML, 200);
            },
        ]);

        $canteen = $this->makeCanteen(['disabled' => false]);
        $canteen->update(['external_id_en' => 597]);

        Artisan::call('canteens:fetch');

        Storage::disk('local')->assertExists("canteen-{$canteen->id}.json");
        Storage::disk('local')->assertExists("canteen-{$canteen->id}-en.json");

        $de = json_decode(Storage::disk('local')->get("canteen-{$canteen->id}.json"), true);
        $en = json_decode(Storage::disk('local')->get("canteen-{$canteen->id}-en.json"), true);
        $this->assertEquals('Gebratenes Sojasteak', $de['lunch'][0]['name']);
        $this->assertEquals('fried soy steak', $en['lunch'][0]['name']);

        app()->setLocale('de');
        $this->assertEquals('Gebratenes Sojasteak', $canteen->refresh()->menu['lunch'][0]['name']);

        app()->setLocale('en');
        $this->assertEquals('fried soy steak', $canteen->refresh()->menu['lunch'][0]['name']);

        app()->setLocale('it');
        $this->assertEquals('Gebratenes Sojasteak', $canteen->refresh()->menu['lunch'][0]['name']);
    }

    public function test_skips_canteens_with_a_disabled_schedule(): void
    {
        Http::fake([
            'https://www.stw-thueringen.de/*' => Http::response('<div class="splGroupWrapper"></div>', 200),
        ]);

        $canteen = $this->makeCanteen(['disabled' => true]);

        Artisan::call('canteens:fetch');

        Http::assertNothingSent();
        Storage::disk('local')->assertMissing("canteen-{$canteen->id}.json");
    }

    public function test_additive_and_allergen_labels_follow_the_app_locale(): void
    {
        app()->setLocale('de');
        $this->assertEquals('enthält Weizen', CanteenMenuParser::allergenLabel('Wz'));
        $this->assertEquals('mit Farbstoff', CanteenMenuParser::additiveLabel('1'));

        app()->setLocale('en');
        $this->assertEquals('contains wheat', CanteenMenuParser::allergenLabel('Wz'));
        $this->assertEquals('with colorants', CanteenMenuParser::additiveLabel('1'));

        // Unknown codes are returned as-is rather than failing.
        $this->assertEquals('XYZ', CanteenMenuParser::allergenLabel('XYZ'));
    }
}
