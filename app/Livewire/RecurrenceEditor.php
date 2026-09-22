<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Edits a Schedule's recurrence as a custom RRULE (frequency, interval,
 * weekly weekdays, end date). Exception dates (RRULE EXDATE) are managed
 * here too, but - unlike $rrule - aren't Modelable, since Livewire only
 * binds one property via wire:model per component tag; the parent listens
 * for the 'exception-dates-updated' event instead.
 */
class RecurrenceEditor extends Component
{
    /**
     * Nullable despite always being assigned a string internally: the
     * generic TrimStringsAndConvertEmptyStringsToNull hook on the parent
     * form coerces an empty-string push-up of this modelable binding back
     * into null, which Livewire then reflects back down into this property.
     */
    #[Modelable]
    public ?string $rrule = '';

    /** @var string[] */
    public array $exceptionDates = [];

    public string $frequency = 'weekly'; // 'weekly' | 'monthly'

    public int $interval = 1;

    /** @var string[] BYDAY codes (MO..SU), weekly frequency only */
    public array $byDay = [];

    public ?string $untilDate = null;

    public string $newExceptionDate = '';

    public function mount(?string $rrule = null, array $exceptionDates = []): void
    {
        $this->rrule = $rrule ?? '';
        $this->exceptionDates = $exceptionDates;
        $this->parseCustomFieldsFromRrule($this->rrule);
    }

    public function updated($name): void
    {
        if (in_array($name, ['frequency', 'interval', 'byDay', 'untilDate'], true) || str_starts_with($name, 'byDay.')) {
            $this->rrule = $this->buildRruleFromCustomFields();
        }
    }

    public function addExceptionDate(): void
    {
        if ($this->newExceptionDate === '' || in_array($this->newExceptionDate, $this->exceptionDates, true)) {
            $this->newExceptionDate = '';

            return;
        }

        $this->exceptionDates[] = $this->newExceptionDate;
        sort($this->exceptionDates);
        $this->newExceptionDate = '';
        $this->dispatch('exception-dates-updated', dates: $this->exceptionDates);
    }

    public function removeExceptionDate(string $date): void
    {
        $this->exceptionDates = array_values(array_diff($this->exceptionDates, [$date]));
        $this->dispatch('exception-dates-updated', dates: $this->exceptionDates);
    }

    private function buildRruleFromCustomFields(): string
    {
        $parts = ['FREQ='.strtoupper($this->frequency)];

        if ($this->interval > 1) {
            $parts[] = 'INTERVAL='.$this->interval;
        }

        if ($this->frequency === 'weekly' && $this->byDay !== []) {
            $parts[] = 'BYDAY='.implode(',', $this->byDay);
        }

        if ($this->untilDate) {
            $parts[] = 'UNTIL='.Carbon::parse($this->untilDate)->endOfDay()->format('Ymd\THis\Z');
        }

        return implode(';', $parts);
    }

    private function parseCustomFieldsFromRrule(string $rrule): void
    {
        $parts = [];
        foreach (explode(';', $rrule) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[strtoupper($key)] = $value;
            }
        }

        $this->frequency = strtolower($parts['FREQ'] ?? 'weekly');
        $this->interval = isset($parts['INTERVAL']) ? max(1, (int) $parts['INTERVAL']) : 1;
        $this->byDay = isset($parts['BYDAY']) ? explode(',', $parts['BYDAY']) : [];
        $this->untilDate = isset($parts['UNTIL'])
            ? Carbon::createFromFormat('Ymd\THis\Z', $parts['UNTIL'])->format('Y-m-d')
            : null;
    }

    public function render()
    {
        return view('livewire.recurrence-editor');
    }
}
