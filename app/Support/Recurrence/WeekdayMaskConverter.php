<?php

namespace App\Support\Recurrence;

/**
 * Converts between the legacy Schedule.repeat weekday mask (digits 1-7,
 * Mo=1..Su=7, e.g. "135") and an equivalent weekly RRULE string. Used by
 * both the legacy-data backfill migration and the "simple" recurrence UI,
 * so the two stay in sync.
 */
class WeekdayMaskConverter
{
    /**
     * Digit (Mo=1..Su=7) => RFC 5545 BYDAY code, in canonical Mo..Su order.
     */
    private const DAYS = [
        1 => 'MO',
        2 => 'TU',
        3 => 'WE',
        4 => 'TH',
        5 => 'FR',
        6 => 'SA',
        7 => 'SU',
    ];

    /**
     * @param  string|null  $repeat  Digits 1-7 (Mo=1..Su=7), any order, e.g. "135".
     */
    public static function toRrule(?string $repeat): ?string
    {
        if (blank($repeat)) {
            return null;
        }

        $byDay = [];
        foreach (self::DAYS as $digit => $code) {
            if (str_contains($repeat, (string) $digit)) {
                $byDay[] = $code;
            }
        }

        if ($byDay === []) {
            return null;
        }

        return 'FREQ=WEEKLY;BYDAY='.implode(',', $byDay);
    }

    /**
     * Reduces an RRULE back to the simple digit mask, only when it's exactly
     * representable that way (weekly, no interval/count/until/other parts).
     * Returns null when the RRULE uses anything the simple UI can't show
     * (used to decide whether switching "custom" -> "simple" is safe).
     */
    public static function fromRrule(?string $rrule): ?string
    {
        if (blank($rrule)) {
            return null;
        }

        $parts = [];
        foreach (explode(';', $rrule) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key === null || $value === null) {
                continue;
            }
            $parts[strtoupper($key)] = $value;
        }

        $allowedKeys = ['FREQ', 'BYDAY'];
        if (array_diff(array_keys($parts), $allowedKeys) !== []) {
            return null;
        }

        if (($parts['FREQ'] ?? null) !== 'WEEKLY' || blank($parts['BYDAY'] ?? null)) {
            return null;
        }

        $codeToDigit = array_flip(self::DAYS);
        $digits = [];
        foreach (explode(',', $parts['BYDAY']) as $code) {
            if (! isset($codeToDigit[$code])) {
                return null;
            }
            $digits[] = $codeToDigit[$code];
        }

        sort($digits);

        return implode('', $digits);
    }
}
