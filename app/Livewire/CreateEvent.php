<?php

namespace App\Livewire;

use App\Livewire\Forms\EventForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Schedule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateEvent extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public EventForm $form;

    private $allTemplates;

    private $template;

    #[On('exception-dates-updated')]
    public function syncExceptionDates(array $dates): void
    {
        $this->form->exceptionDates = $dates;
    }

    #[On('rrule-updated')]
    public function syncRrule(?string $rrule): void
    {
        $this->form->rrule = $rrule;
    }

    public function save()
    {
        $this->authorize('create', Event::class);
        $res = $this->form->store();
        if ($res) {
            flash(__('The event has been created'))->success();
            $this->redirectRoute('events.index');
        }
    }

    public function mount($allTemplates, $template)
    {
        $this->allTemplates = $allTemplates;
        if ($template) {
            $this->form->setTemplate($template);
        }
    }

    #[Computed]
    public function duration()
    {
        return Schedule::calculateDuration($this->form->start, $this->form->end, $this->form->start_time, $this->form->end_time, null, null, $this->form->rrule, $this->form->exceptionDates);
    }

    public function render()
    {
        return view(
            'livewire.edit-event',
            [
                'allTemplates' => $this->allTemplates,
                'allMenus' => Menu::ofRealm(auth()->user()->realm_id)->select(['id', 'name'])->orderBy('name')->get(),
            ]
        );
    }
}
