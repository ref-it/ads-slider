<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventsImport;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaldavEventsImportTest extends TestCase
{
    use RefreshDatabase;

    private Realm $realm;

    private User $user;

    private const URL = 'https://cloud.example.test/remote.php/dav/public-calendars/abc123/?export';

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache store outlives RefreshDatabase between tests.
        Cache::flush();

        $this->realm = Realm::factory()->create();
        $this->user = User::factory()->create(['realm_id' => $this->realm->id]);
    }

    private function makeImport(array $overrides = []): EventsImport
    {
        return EventsImport::factory()->create(array_merge([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'import_url' => self::URL,
            'source_type' => 'caldav',
            'import_disabled' => false,
        ], $overrides));
    }

    public function test_imports_a_simple_vevent_with_basic_auth(): void
    {
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//EN
        BEGIN:VEVENT
        UID:simple-event-1@example.test
        DTSTAMP:20260101T000000Z
        DTSTART:20261001T180000Z
        DTEND:20261001T200000Z
        SUMMARY:Simple Party
        LOCATION:Main Hall
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([
            self::URL => Http::response($ics, 200),
        ]);

        $import = $this->makeImport([
            'caldav_username' => 'alice',
            'caldav_password' => 'secret',
        ]);

        $exitCode = Artisan::call('import:events', ['source' => [$import->id]]);
        $this->assertEquals(0, $exitCode);

        Http::assertSent(function ($request) {
            return $request->url() === self::URL
                && $request->hasHeader('Authorization')
                && str_starts_with($request->header('Authorization')[0], 'Basic ');
        });

        $event = Event::where('events_import_id', $import->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals('Simple Party', $event->name);
        $this->assertEquals('Main Hall', $event->place);
        $this->assertNull($event->schedule->rrule);
    }

    public function test_imports_a_recurring_vevent_with_rrule(): void
    {
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//EN
        BEGIN:VEVENT
        UID:recurring-event-1@example.test
        DTSTAMP:20260101T000000Z
        DTSTART:20261005T180000Z
        DTEND:20261005T200000Z
        RRULE:FREQ=WEEKLY;BYDAY=MO
        SUMMARY:Weekly Quiz Night
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([
            self::URL => Http::response($ics, 200),
        ]);

        $import = $this->makeImport();

        $exitCode = Artisan::call('import:events', ['source' => [$import->id]]);
        $this->assertEquals(0, $exitCode);

        $event = Event::where('events_import_id', $import->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=MO', $event->schedule->rrule);
    }

    public function test_exdate_and_recurrence_id_override_become_exception_and_standalone_event(): void
    {
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//EN
        BEGIN:VEVENT
        UID:override-series@example.test
        DTSTAMP:20260101T000000Z
        DTSTART:20261005T180000Z
        DTEND:20261005T200000Z
        RRULE:FREQ=WEEKLY;BYDAY=MO
        EXDATE:20261019T180000Z
        SUMMARY:Weekly Meeting
        END:VEVENT
        BEGIN:VEVENT
        UID:override-series@example.test
        RECURRENCE-ID:20261012T180000Z
        DTSTAMP:20260101T000000Z
        DTSTART:20261013T190000Z
        DTEND:20261013T210000Z
        SUMMARY:Weekly Meeting (moved)
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([
            self::URL => Http::response($ics, 200),
        ]);

        $import = $this->makeImport();

        Artisan::call('import:events', ['source' => [$import->id]]);

        $events = Event::where('events_import_id', $import->id)->get();
        $this->assertCount(2, $events);

        $master = $events->firstWhere('name', 'Weekly Meeting');
        $override = $events->firstWhere('name', 'Weekly Meeting (moved)');

        $this->assertNotNull($master);
        $this->assertNotNull($override);
        $this->assertNull($override->schedule->rrule);

        $exceptionDates = $master->schedule->exceptions->map(fn ($e) => $e->exception_date->toDateString())->sort()->values()->all();
        // The explicit EXDATE and the moved occurrence's original date are both excluded from the master series.
        $this->assertEquals(['2026-10-12', '2026-10-19'], $exceptionDates);
    }

    public function test_protected_local_event_is_not_overwritten_and_removed_events_are_deleted(): void
    {
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//EN
        BEGIN:VEVENT
        UID:kept-event@example.test
        DTSTAMP:20260101T000000Z
        DTSTART:20261001T180000Z
        DTEND:20261001T200000Z
        SUMMARY:Still There
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([
            self::URL => Http::response($ics, 200),
        ]);

        $import = $this->makeImport();

        // First run creates both events.
        Artisan::call('import:events', ['source' => [$import->id]]);

        $keptEvent = Event::where('events_import_id', $import->id)->where('name', 'Still There')->first();

        $goneEvent = Event::factory()->create([
            'realm_id' => $this->realm->id,
            'events_import_id' => $import->id,
            'import_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'No Longer On The Calendar',
        ]);
        \App\Models\Schedule::factory()->create([
            'scheduleable_type' => 'EV',
            'scheduleable_id' => $goneEvent->id,
            'realm_id' => $this->realm->id,
            'start' => now()->addDay()->toDateString(),
        ]);

        $keptEvent->is_protected = true;
        $keptEvent->name = 'Locally Renamed';
        $keptEvent->save();

        Artisan::call('import:events', ['source' => [$import->id], '--force' => true]);

        $this->assertDatabaseHas('events', ['id' => $keptEvent->id, 'name' => 'Locally Renamed']);
        $this->assertDatabaseMissing('events', ['id' => $goneEvent->id]);
    }

    public function test_reports_failure_on_http_error(): void
    {
        Http::fake([
            self::URL => Http::response('Unauthorized', 401),
        ]);

        $import = $this->makeImport();

        $exitCode = Artisan::call('import:events', ['source' => [$import->id]]);
        $this->assertEquals(1, $exitCode);
    }

    public function test_reports_failure_on_malformed_ics(): void
    {
        Http::fake([
            self::URL => Http::response('this is not an ICS document', 200),
        ]);

        $import = $this->makeImport();

        $exitCode = Artisan::call('import:events', ['source' => [$import->id]]);
        $this->assertEquals(1, $exitCode);
    }
}
