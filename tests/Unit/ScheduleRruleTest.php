<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Support\Recurrence\WeekdayMaskConverter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Mirrors tests/Unit/EventsTest.php scenarios but with the digit-mask
 * `repeat` translated to an equivalent RRULE, to prove the new RRULE path
 * in Schedule::calculateStartDateTime/calculateEndDateTime behaves exactly
 * like the legacy path for the cases it can express, plus new cases
 * (exceptions, interval, monthly) the legacy mask can't express at all.
 */
class ScheduleRruleTest extends TestCase
{
    private Carbon $referenceDate;

    private Carbon $referenceDate15;

    private const string TODAY = '2022-03-23'; // dow: 3 (Wednesday)

    private const string TOMORROW = '2022-03-24'; // dow: 4

    private const string YESTERDAY = '2022-03-22'; // dow: 2

    protected function setUp(): void
    {
        parent::setUp();

        $this->referenceDate = new Carbon(self::TODAY.' 12:00:00');
        $this->referenceDate15 = new Carbon(self::TODAY.' 15:00:00');
    }

    private function rruleFor(string $repeatDigits): string
    {
        return WeekdayMaskConverter::toRrule($repeatDigits);
    }

    public function test_today_is_today()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            self::TODAY,
            self::TODAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate,
            $this->rruleFor('1234567'),
        );
        $this->assertEquals(self::TODAY.' 14:00:00', $real_start_date);
    }

    public function test_overnight_event()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            self::TODAY, // 3
            self::TOMORROW, // 4
            '20:00:00',
            '13:00:00',
            null,
            $this->referenceDate, // 3, 12:00:00
            $this->rruleFor('3'),
        );
        $this->assertEquals(self::TODAY.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event2()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            self::TODAY, // 3
            self::TOMORROW, // 4
            '20:00:00',
            '13:00:00',
            null,
            $this->referenceDate, // 3, 12:00:00
            $this->rruleFor('4'),
        );
        $this->assertEquals(self::TOMORROW.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event_starting_tomorrow_without_boundaries()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            null,
            null,
            '20:00:00',
            '13:00:00',
            null,
            $this->referenceDate, // 3, 12:00:00
            $this->rruleFor('4'),
        );
        $this->assertEquals(self::TOMORROW.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event_without_boundaries_still_running()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            null,
            null,
            '20:00:00',
            '13:00:00',
            null,
            $this->referenceDate, // 3, 12:00:00
            $this->rruleFor('2'),
        );
        $this->assertEquals((new Carbon(self::YESTERDAY.' 20:00:00'))->format('Y-m-d H:i:s'), $real_start_date);
    }

    public function test_overnight_event_the_day_after_it_finishes()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            self::YESTERDAY,
            self::TODAY,
            '20:00:00',
            '13:00:00',
            null,
            $this->referenceDate15, // 3, 15:00:00
            $this->rruleFor('2'),
        );
        $this->assertEquals((new Carbon(self::YESTERDAY.' 20:00:00'))->addDays(7)->format('Y-m-d H:i:s'), $real_start_date);
    }

    public function test_does_not_repeat_end_with_rrule()
    {
        $real_end_date = Schedule::calculateEndDateTime(
            self::TODAY,
            self::TODAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate,
            $this->rruleFor('1234567'),
        );
        $this->assertEquals(self::TODAY.' 17:00:59', $real_end_date);
    }

    /* ---------------------------------------------------------------- */
    /* New behaviour: things the legacy digit mask cannot express at all */
    /* ---------------------------------------------------------------- */

    public function test_exception_date_is_skipped()
    {
        // Every Wednesday, but today (an exception) is skipped -> next Wednesday.
        $real_start_date = Schedule::calculateStartDateTime(
            self::TODAY,
            null,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate, // Wed 2022-03-23, 12:00
            'FREQ=WEEKLY;BYDAY=WE',
            [self::TODAY],
        );
        $this->assertEquals('2022-03-30 14:00:00', $real_start_date);
    }

    public function test_interval_every_other_week()
    {
        // Anchored on today (an "on" week); next week should be skipped.
        $rrule = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE';

        $onWeek = Schedule::calculateStartDateTime(
            self::TODAY,
            null,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate,
            $rrule,
        );
        $this->assertEquals(self::TODAY.' 14:00:00', $onWeek);

        $offWeekReference = (new Carbon(self::TODAY))->addWeek()->setTime(12, 0);
        $offWeek = Schedule::calculateStartDateTime(
            self::TODAY,
            null,
            '14:00:00',
            '17:00:00',
            null,
            $offWeekReference,
            $rrule,
        );
        $this->assertEquals((new Carbon(self::TODAY))->addWeeks(2)->format('Y-m-d').' 14:00:00', $offWeek);
    }

    public function test_rrule_with_until_stops_repeating()
    {
        $rrule = 'FREQ=WEEKLY;BYDAY=WE;UNTIL='.(new Carbon(self::TODAY))->format('Ymd\THis\Z');

        // The day it ends: still occurs.
        $lastOccurrence = Schedule::calculateStartDateTime(
            self::TODAY,
            null,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate,
            $rrule,
        );
        $this->assertEquals(self::TODAY.' 14:00:00', $lastOccurrence);
    }
}
