<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\EventController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventsList extends Component
{
    use WithPagination;

    public $search = '';

    public bool $showPast = false;

    public function clearSearch()
    {
        $this->reset('search');
        $this->searchUpdates();
    }

    public function updatedShowPast(): void
    {
        $this->resetPage();
    }

    public function deleteEvent($eventID)
    {
        $event = Event::ofRealm(auth()->user()->realm_id)->findOrFail($eventID);

        $this->authorize('delete', $event);
        try {
            $event->delete();
            event(new SecurityAuditEvent(
                action: 'event.deleted',
                description: "Event deleted from EventsList: '{$event->name}' (ID: {$event->id})",
                userId: auth()->id(),
                realmId: $event->realm_id
            ));
            session()->flash('success', __('Event deleted'));
        } catch (\Exception $e) {
            session()->flash('error', __('Event could not be deleted.'));
            Log::error($e->getMessage());
        }
    }

    #[Computed()]
    private function allEvents()
    {
        if ($this->showPast) {
            return Event::with(['schedule'])->ofRealm(auth()->user()->realm_id)->whereHas('schedule', function ($query) {
                $query->where('end', '<', Carbon::today()->toDateString());
            })->with(['user']);
        }

        return EventController::getNotEndedEvents();
    }

    #[Computed()]
    public function events()
    {
        return $this->allEvents->with('events_import')->where('name', 'LIKE', "%{$this->search}%")->paginate(10);
    }

    public function searchUpdates(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view(
            'livewire.events-list')
            ->title(__('Events'))
            ->layout('livewire.master', ['header' => null]);
    }
}
