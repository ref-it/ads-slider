<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\HappyHourForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Event;
use App\Models\HappyHour;
use Livewire\Component;

class EditHappyHour extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public HappyHourForm $hhForm;

    public Event $event;

    public bool $isManagerUpdating = false;

    public function mount(Event $event, bool $isManagerUpdating, ?HappyHour $happyHour = null): void
    {
        $this->event = $event;
        $this->hhForm->setParentEvent($event);
        $this->isManagerUpdating = $isManagerUpdating;

        if ($happyHour) {
            $this->hhForm->loadHappyHour($happyHour);
        } else {
            $this->hhForm->fillDefaults();
        }
    }

    private function backToEvent()
    {
        if ($this->isManagerUpdating) {
            return $this->redirectRoute('events.avedit', ['event' => $this->event->id, 'api_token' => $this->event->api_token]);
        }

        return $this->redirectRoute('events.edit', ['event' => $this->event->id]);
    }

    public function createHappyHour()
    {
        $this->authorize('create', [HappyHour::class, $this->event]);
        $this->hhForm->saveHappyHour();
        flash(__('Happy hour created'))->success();

        return $this->backToEvent();
    }

    public function createHappyHourAsManager()
    {
        $this->authorize('createAsManager', [HappyHour::class, $this->event]);
        $this->hhForm->saveHappyHour();
        flash(__('Happy hour created'))->success();

        return $this->backToEvent();
    }

    public function updateHappyHour()
    {
        $hh = $this->event->happy_hours()->findOrFail($this->hhForm->happyHourId);
        $this->authorize('update', $hh);
        $this->hhForm->saveHappyHour();
        flash(__('Happy hour updated'))->success();

        return $this->backToEvent();
    }

    public function updateHappyHourAsManager()
    {
        $hh = $this->event->happy_hours()->findOrFail($this->hhForm->happyHourId);
        $this->authorize('updateAsManager', [$hh, $this->event]);
        $this->hhForm->saveHappyHour();
        flash(__('Happy hour updated'))->success();

        return $this->backToEvent();
    }

    public function deleteHappyHour()
    {
        $hh = $this->event->happy_hours()->findOrFail($this->hhForm->happyHourId);
        $this->authorize('delete', $hh);
        $this->auditDelete($hh);

        return $this->backToEvent();
    }

    public function deleteHappyHourAsManager()
    {
        $hh = $this->event->happy_hours()->findOrFail($this->hhForm->happyHourId);
        $this->authorize('deleteAsManager', [$hh, $this->event]);
        $hh->delete();
        flash(__('Happy hour deleted'))->success();

        return $this->backToEvent();
    }

    private function auditDelete(HappyHour $hh): void
    {
        $eventName = $this->event->name;
        $hh->delete();
        event(
            new SecurityAuditEvent(
                action: 'happy_hour.deleted',
                description: "Happy hour '{$hh->drink}' (ID: {$hh->id}) of event '{$eventName}' deleted by user ID: ".auth()->id(),
                userId: auth()->id(),
                realmId: $hh->realm_id,
                context: ['happy_hour_id' => $hh->id, 'drink' => $hh->drink]
            )
        );
        flash(__('Happy hour deleted'))->success();
    }

    public function render()
    {
        return view('livewire.edit-happy-hour', [
            'deleteButton' => 'deleteHappyHour'.($this->isManagerUpdating == true ? 'AsManager' : ''),
        ]);
    }
}
