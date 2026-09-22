<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\SlideForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditSlide extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull, WithFileUploads;

    public SlideForm $form;

    public $monitors = [];

    public $action = 'create';

    public function mount($type = null, ?Schedule $slide = null)
    {
        if ($slide && $slide->exists) {
            $this->authorize('update', $slide);
        } else {
            $this->authorize('create', Schedule::class);
        }

        $this->action = $slide && $slide->exists ? 'edit' : 'create';
        $this->monitors = Monitor::ofRealm(auth()->user()->realm_id)->orderBy('name')->get();

        if ($slide && $slide->exists) {
            $this->form->setSchedule($slide);
        } elseif ($type === 'video') {
            $this->form->setMediaType('video');
        } else {
            $this->form->setMediaType('picture');
        }
    }

    public function createSlide()
    {
        $this->authorize('create', Schedule::class);
        $this->form->store();
        flash(__('The slide has been created'))->success();
        Log::channel('crud')->info('Slide created', [
            'slide' => $this->form->schedule,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('slides.index');
    }

    public function updateSlide()
    {
        $this->authorize('update', $this->form->schedule);
        $this->form->store();
        flash(__('The slide has been updated'))->success();
        Log::channel('crud')->info('Slide updated', [
            'slide' => $this->form->schedule,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('slides.index');
    }

    public function deleteSlide()
    {
        $this->authorize('delete', $this->form->schedule);
        $schedule = $this->form->schedule;

        $this->form->delete();

        event(new SecurityAuditEvent(
            action: 'schedule.deleted',
            description: "Slide schedule (ID: {$schedule->id}) deleted for {$schedule->scheduleable_type} ID: {$schedule->scheduleable_id}",
            userId: auth()->id(),
            realmId: $schedule->realm_id,
            context: [
                'schedule_id' => $schedule->id,
                'scheduleable_id' => $schedule->scheduleable_id,
                'type' => $schedule->scheduleable_type,
            ]
        ));

        $this->redirectRoute('slides.index');
    }

    #[Computed]
    public function currentPicture(): ?Picture
    {
        if ($this->form->media_type !== 'picture' || ! $this->form->media_id) {
            return null;
        }

        return Picture::with('sources')->find($this->form->media_id);
    }

    public function render()
    {
        return view('livewire.edit-slide');
    }
}
