<?php

namespace Tests\Unit;

use App\Support\Recurrence\RecurrenceOccurrences;
use DateTimeImmutable;
use Tests\TestCase;

class RecurrenceOccurrencesTest extends TestCase
{
    public function test_occurs_on_matches_weekly_byday_pattern(): void
    {
        $dtStart = new DateTimeImmutable('2026-01-05'); // a Monday
        $rrule = 'FREQ=WEEKLY;BYDAY=MO,WE,FR';

        $this->assertTrue(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-07'))); // Wed
        $this->assertFalse(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-08'))); // Thu
        $this->assertTrue(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-19'))); // Mon, 2 weeks later
    }

    public function test_occurs_on_respects_interval(): void
    {
        $dtStart = new DateTimeImmutable('2026-01-05'); // a Monday
        $rrule = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO';

        $this->assertTrue(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-05')));
        $this->assertFalse(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-12'))); // next Monday, skipped
        $this->assertTrue(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-19')));
    }

    public function test_occurs_on_respects_until(): void
    {
        $dtStart = new DateTimeImmutable('2026-01-05');
        $rrule = 'FREQ=WEEKLY;BYDAY=MO;UNTIL=20260112T000000Z';

        $this->assertTrue(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-12')));
        $this->assertFalse(RecurrenceOccurrences::occursOn($rrule, $dtStart, new DateTimeImmutable('2026-01-19')));
    }

    public function test_next_occurrence_on_or_after_returns_null_past_the_end(): void
    {
        $dtStart = new DateTimeImmutable('2026-01-05');
        $rrule = 'FREQ=WEEKLY;BYDAY=MO;COUNT=2';

        $this->assertNull(RecurrenceOccurrences::nextOccurrenceOnOrAfter($rrule, $dtStart, new DateTimeImmutable('2026-02-01')));
    }

    public function test_next_occurrence_on_or_after_finds_the_first_matching_date(): void
    {
        $dtStart = new DateTimeImmutable('2026-01-05');
        $rrule = 'FREQ=WEEKLY;BYDAY=FR';

        $next = RecurrenceOccurrences::nextOccurrenceOnOrAfter($rrule, $dtStart, new DateTimeImmutable('2026-01-06'));

        $this->assertSame('2026-01-09', $next->format('Y-m-d'));
    }
}
