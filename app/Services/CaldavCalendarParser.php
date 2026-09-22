<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Reader;

/**
 * Fetches a CalDAV/ICS resource and maps its VEVENTs to the same normalized
 * array shape the JSON importer produces (see ImportEvents::import()), so
 * both sources share the same match/skip/validate/save logic. A VEVENT with
 * an RRULE is passed through as-is (Schedule.rrule uses the same Sabre
 * RRuleIterator under the hood, so no separate expansion is needed);
 * EXDATE and RECURRENCE-ID overrides become schedule_exceptions/standalone
 * events respectively.
 */
class CaldavCalendarParser
{
    public function __construct(private readonly int $importId) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $url, ?string $username = null, ?string $password = null): array
    {
        $url = preg_replace('#^webcal://#i', 'https://', $url);

        $request = Http::withOptions([
            'verify' => app()->environment('production'),
        ]);

        if (! blank($username)) {
            $request = $request->withBasicAuth($username, $password ?? '');
        }

        $response = $request->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("HTTP {$response->status()} while fetching {$url}");
        }

        $calendar = Reader::read($response->body());

        if (! $calendar instanceof VCalendar || ! isset($calendar->VEVENT)) {
            throw new RuntimeException("The response from {$url} is not a valid iCalendar document");
        }

        return $this->normalize($calendar);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalize(VCalendar $calendar): array
    {
        $byUid = [];
        foreach ($calendar->select('VEVENT') as $vevent) {
            $byUid[(string) $vevent->UID][] = $vevent;
        }

        $events = [];
        foreach ($byUid as $uid => $veventsForUid) {
            $master = null;
            $overrides = [];
            foreach ($veventsForUid as $vevent) {
                if (isset($vevent->{'RECURRENCE-ID'})) {
                    $overrides[] = $vevent;
                } else {
                    $master = $vevent;
                }
            }

            if ($master === null) {
                foreach ($overrides as $override) {
                    $events[] = $this->mapEvent($override, $uid, $this->recurrenceIdDate($override));
                }

                continue;
            }

            $exceptionDates = $this->exceptionDatesOf($master);
            foreach ($overrides as $override) {
                $recurrenceId = $this->recurrenceIdDate($override);
                if ($recurrenceId !== null) {
                    $exceptionDates[] = $recurrenceId;
                }
                $events[] = $this->mapEvent($override, $uid, $recurrenceId);
            }

            $events[] = $this->mapEvent($master, $uid, null, array_values(array_unique($exceptionDates)));
        }

        return $events;
    }

    /**
     * @return string[] 'Y-m-d' dates
     */
    private function exceptionDatesOf(VEvent $vevent): array
    {
        $dates = [];
        foreach ($vevent->select('EXDATE') as $exdate) {
            foreach ($exdate->getParts() as $part) {
                $dates[] = Carbon::parse($part)->format('Y-m-d');
            }
        }

        return $dates;
    }

    private function recurrenceIdDate(VEvent $vevent): ?string
    {
        if (! isset($vevent->{'RECURRENCE-ID'})) {
            return null;
        }

        return Carbon::parse((string) $vevent->{'RECURRENCE-ID'})->format('Y-m-d');
    }

    /**
     * @param  string[]  $exceptionDates  Only meaningful for a recurring master event.
     * @return array<string, mixed>
     */
    private function mapEvent(VEvent $vevent, string $uid, ?string $recurrenceId, array $exceptionDates = []): array
    {
        $dtstart = $vevent->DTSTART->getDateTime();
        $isAllDay = $vevent->DTSTART->getValueType() === 'DATE';

        if (isset($vevent->DTEND)) {
            $dtend = $vevent->DTEND->getDateTime();
        } elseif (isset($vevent->DURATION)) {
            $dtend = (clone $dtstart)->add(DateTimeParser::parseDuration((string) $vevent->DURATION));
        } else {
            $dtend = clone $dtstart;
        }

        $importId = Uuid::uuid5(Uuid::NAMESPACE_URL, $this->importId.'|'.$uid.($recurrenceId !== null ? '|'.$recurrenceId : ''))->toString();

        $updatedOn = isset($vevent->{'LAST-MODIFIED'})
            ? Carbon::instance($vevent->{'LAST-MODIFIED'}->getDateTime())
            : (isset($vevent->DTSTAMP) ? Carbon::instance($vevent->DTSTAMP->getDateTime()) : Carbon::now());

        $link = isset($vevent->URL) ? (string) $vevent->URL : null;
        if ($link !== null && ! str_starts_with($link, 'https://')) {
            $link = null;
        }

        $data = [
            'import_id' => $importId,
            'name' => (string) ($vevent->SUMMARY ?? $uid),
            'start' => Carbon::instance($dtstart)->format('Y-m-d'),
            'start_time' => $isAllDay ? '00:00:00' : Carbon::instance($dtstart)->format('H:i:s'),
            'end' => Carbon::instance($dtend)->format('Y-m-d'),
            'end_time' => $isAllDay ? '23:59:00' : Carbon::instance($dtend)->format('H:i:s'),
            'place' => isset($vevent->LOCATION) ? (string) $vevent->LOCATION : null,
            'link' => $link,
            'cancelled' => isset($vevent->STATUS) && strtoupper((string) $vevent->STATUS) === 'CANCELLED',
            'updated_on' => $updatedOn->toIso8601String(),
        ];

        if (isset($vevent->RRULE)) {
            $data['rrule'] = (string) $vevent->RRULE;
            $data['exceptionDates'] = $exceptionDates;
        }

        return $data;
    }
}
