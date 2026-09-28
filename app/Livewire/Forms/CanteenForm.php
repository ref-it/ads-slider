<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Canteen;
use App\Models\Monitor;
use App\Models\ScheduleException;
use App\Rules\ValidRrule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CanteenForm extends Form
{
    use ErrorBanner;

    public ?Canteen $canteen = null;

    #[Validate('required|max:191')]
    public $name = '';

    #[Validate('required|integer')]
    public $external_id;

    /**
     * The English-edition resources_id (stw-thueringen.de uses a separate
     * numeric ID per language edition of the same canteen). Optional.
     */
    #[Validate('nullable|integer')]
    public $external_id_en;

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate(['nullable', 'string', new ValidRrule])]
    public $rrule = '';

    /**
     * @var string[] 'Y-m-d' dates the recurrence should skip (RRULE EXDATE).
     */
    public $exceptionDates = [];

    #[Validate('boolean')]
    public $disabled = false;

    /**
     * Monitors this canteen's menu should be shown on. Deliberately not
     * required: a canteen with no monitors attached simply isn't shown
     * anywhere yet, unlike Picture where an empty selection means "show
     * everywhere".
     *
     * @var int[]
     */
    #[Validate('array')]
    public $monitor_ids = [];

    public function setCanteen(Canteen $canteen)
    {
        $this->canteen = $canteen;
        $this->name = $canteen->name;
        $this->external_id = $canteen->external_id;
        $this->external_id_en = $canteen->external_id_en;
        $this->start_time = $canteen->schedule?->start_time ? substr($canteen->schedule->start_time, 0, 5) : '';
        $this->end_time = $canteen->schedule?->end_time ? substr($canteen->schedule->end_time, 0, 5) : '';
        $this->rrule = $canteen->schedule?->rrule ?? '';
        $this->exceptionDates = $canteen->schedule
            ? $canteen->schedule->exceptions->map(fn (ScheduleException $e) => $e->exception_date->toDateString())->all()
            : [];
        $this->disabled = (bool) ($canteen->schedule?->disabled ?? false);
        $this->monitor_ids = $canteen->monitors->pluck('id')->all();
    }

    public function delete()
    {
        $logContext = [
            'id' => $this->canteen->id,
            'name' => $this->canteen->name,
            'user' => auth()->id(),
        ];

        $this->canteen->delete();

        flash(__('The canteen has been deleted'))->success();
        Log::channel('crud')->warning('Canteen deleted', $logContext);
    }

    public function store(): bool
    {
        $validated = $this->validate();

        if (! $this->canteen) {
            $this->canteen = new Canteen;
            $this->canteen->realm_id = Auth::user()->realm_id;
            $this->canteen->user_id = Auth::id();
        }

        $this->canteen->fill([
            'name' => $validated['name'],
            'external_id' => $validated['external_id'],
            'external_id_en' => $validated['external_id_en'] ?? null,
        ]);

        try {
            DB::beginTransaction();
            $this->canteen->save();

            $schedule = $this->canteen->schedule()->updateOrCreate([], [
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'rrule' => $this->rrule ?: null,
                'disabled' => $validated['disabled'] ?? false,
                'realm_id' => $this->canteen->realm_id,
                'user_id' => $this->canteen->user_id,
            ]);

            $exceptionDates = $this->rrule ? array_unique($this->exceptionDates) : [];
            $schedule->exceptions()->whereNotIn('exception_date', $exceptionDates)->delete();
            $existing = $schedule->exceptions()->pluck('exception_date')->map(fn ($d) => $d->toDateString())->all();
            foreach (array_diff($exceptionDates, $existing) as $date) {
                $schedule->exceptions()->create(['exception_date' => $date]);
            }

            $validMonitorIds = Monitor::ofRealm($this->canteen->realm_id)
                ->whereIn('id', (array) $this->monitor_ids)
                ->pluck('id');
            $this->canteen->monitors()->sync($validMonitorIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->setErrorMessage('The canteen could not be stored, please report this error.');
            Log::error('Canteen creation failed', [
                'canteen' => $this->canteen,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        Log::channel('crud')->info('Canteen stored', [
            'canteen' => $this->canteen,
            'user' => Auth::id(),
        ]);

        return true;
    }
}
