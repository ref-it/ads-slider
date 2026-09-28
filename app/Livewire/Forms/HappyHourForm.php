<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Event;
use App\Models\HappyHour;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class HappyHourForm extends Form
{
    use ErrorBanner;

    public ?Event $event = null;

    public ?int $happyHourId = null;

    #[Validate('required|max:20')]
    public $drink = '';

    #[Validate('required|max:20')]
    public $price = '';

    #[Validate('nullable|max:40')]
    public $info = '';

    #[Validate('date|required')]
    public $start;

    #[Validate('date|after:start|required')]
    public $end;

    public function setParentEvent(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Pre-fill start/end for a new Happy Hour: starting when the event
     * (next occurrence) starts, running for one hour, so the form isn't
     * blank and the picked defaults stay within the event's timeframe.
     */
    public function fillDefaults(): void
    {
        $start = $this->event?->real_start_date ? Carbon::parse($this->event->real_start_date) : now();
        $end = $start->copy()->addHour();

        $this->start = $start->format('Y-m-d\TH:i');
        $this->end = $end->format('Y-m-d\TH:i');
    }

    public function loadHappyHour(HappyHour $hh)
    {
        $this->happyHourId = $hh->id;
        $this->drink = $hh->drink;
        $this->price = $hh->price;
        $this->info = $hh->info;
        $this->start = $hh->start;
        $this->end = $hh->end;
    }

    public function resetForm()
    {
        $this->happyHourId = null;
        $this->drink = '';
        $this->price = '';
        $this->info = '';
        $this->start = '';
        $this->end = '';
    }

    public function saveHappyHour(): HappyHour
    {
        $this->validate();

        $attributes = [
            'drink' => $this->drink,
            'price' => $this->price,
            'info' => $this->info,
            'start' => $this->start,
            'end' => $this->end,
        ];

        if ($this->happyHourId) {
            $hh = $this->event->happy_hours()->findOrFail($this->happyHourId);
            $hh->update($attributes);

            return $hh;
        }

        return $this->event->happy_hours()->create($attributes);
    }
}
