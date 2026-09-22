<?php

namespace App\Support\Recurrence;

use DateTimeImmutable;
use DateTimeInterface;
use Sabre\VObject\Recur\RRuleIterator;

/**
 * Thin wrapper around Sabre\VObject\Recur\RRuleIterator, isolated so
 * Schedule.php doesn't call into Sabre directly and this piece stays
 * independently unit-testable.
 */
class RecurrenceOccurrences
{
    /**
     * Whether the recurrence pattern has an occurrence on the given date
     * (date part only; time of day is irrelevant here, Schedule tracks
     * start_time/end_time separately from the recurrence pattern).
     */
    public static function occursOn(string $rrule, DateTimeInterface $dtStart, DateTimeInterface $date): bool
    {
        $date = self::atMidnight($date);
        $occurrence = self::nextOccurrenceOnOrAfter($rrule, $dtStart, $date);

        return $occurrence !== null && $occurrence->format('Y-m-d') === $date->format('Y-m-d');
    }

    /**
     * The first occurrence on or after $after, or null if the pattern has
     * no (more) occurrences (e.g. past its UNTIL/COUNT limit).
     */
    public static function nextOccurrenceOnOrAfter(string $rrule, DateTimeInterface $dtStart, DateTimeInterface $after): ?DateTimeInterface
    {
        // Both anchor and target are normalized to midnight: only the date
        // matters here, and RRuleIterator::fastForward() compares full
        // datetimes, so a $dtStart at midnight vs. an $after later in the
        // day (e.g. noon) would otherwise skip past a same-day occurrence.
        $iterator = new RRuleIterator($rrule, self::atMidnight($dtStart));
        $iterator->fastForward(self::atMidnight($after));

        return $iterator->valid() ? $iterator->current() : null;
    }

    private static function atMidnight(DateTimeInterface $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d'));
    }
}
