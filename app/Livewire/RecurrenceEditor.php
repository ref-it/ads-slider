<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;

/**
 * Edits a Schedule's recurrence as a custom RRULE (frequency, interval,
 * weekly weekdays, end date). $rrule is pushed up to the parent via the
 * 'rrule-updated' event rather than a #[Modelable] wire:model binding:
 * Livewire's Modelable synth for nested components relies on its eval-based
 * expression evaluator, which breaks under Livewire's CSP-safe mode. Exception
 * dates (RRULE EXDATE) already used this same dispatch pattern beforehand,
 * since Livewire only binds one property via wire:model per component tag.
 */
class RecurrenceEditor extends Component
{
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
            $this->dispatch('rrule-updated', rrule: $this->rrule);
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
