<?php

namespace App\Livewire;

use App\Livewire\Forms\CanteenForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Canteen;
use App\Models\Monitor;
use App\Models\Schedule;
use Livewire\Attributes\On;
use Livewire\Component;

class EditCanteen extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public CanteenForm $form;

    public $action = 'create';

    public $allMonitors = [];

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

    public function createCanteen()
    {
        $this->authorize('create', Schedule::class);
        if ($this->form->store()) {
            flash(__('The canteen has been created'))->success();
            $this->redirectRoute('canteens.index');
        }
    }

    public function updateCanteen()
    {
        $this->authorize('update', $this->form->canteen->schedule);
        if ($this->form->store()) {
            flash(__('The canteen has been updated'))->success();
            $this->redirectRoute('canteens.index');
        }
    }

    public function deleteCanteen()
    {
        $this->authorize('delete', $this->form->canteen->schedule);
        $this->form->delete();
        $this->skipRender();
        $this->redirectRoute('canteens.index');
    }

    public function mount($action, ?Canteen $canteen = null)
    {
        if ($canteen && $canteen->exists) {
            $this->authorize('update', $canteen->schedule);
        } else {
            $this->authorize('create', Schedule::class);
        }
        $this->action = $action;
        $this->allMonitors = Monitor::ofRealm(auth()->user()->realm_id)->orderBy('name')->get(['id', 'name']);
        if ($canteen && $canteen->exists) {
            $this->form->setCanteen($canteen);
        }
    }

    public function render()
    {
        return view('livewire.edit-canteen');
    }
}
