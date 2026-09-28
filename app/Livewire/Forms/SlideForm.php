<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\Video;
use App\Providers\ItemUpdated;
use App\Rules\ValidRrule;
use App\Traits\UploadTrait;
use Carbon\Carbon;
use ColorThief\ColorThief;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SlideForm extends Form
{
    use ErrorBanner, UploadTrait;

    public ?Schedule $schedule = null;

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate('present|date|nullable|required_with:end')]
    public $start = null;

    #[Validate('present|required_with:start|date|nullable|after_or_equal:start')]
    public $end = null;

    #[Validate(['nullable', 'string', new ValidRrule])]
    public $rrule = '';

    /**
     * @var string[] 'Y-m-d' dates the recurrence should skip (RRULE EXDATE).
     */
    public $exceptionDates = [];

    #[Validate('boolean')]
    public $disabled = false;

    /**
     * 'picture' or 'video'. Fixed once a schedule already exists, since a
     * Picture/Video belongs to exactly one Slide and is never re-typed.
     */
    #[Validate('required|in:picture,video')]
    public $media_type = 'picture';

    /**
     * The Picture/Video this slide owns. Only set once the slide (and its
     * media) already exists; a new slide always uploads a brand-new file.
     */
    public $media_id = null;

    /**
     * Media metadata, always present: prefilled from the existing
     * Picture/Video when editing, entered fresh when creating. Validated
     * conditionally in validateMedia() since the rules (image vs. mp4,
     * which fields even apply) depend on media_type and create-vs-edit.
     */
    public $media_name = '';

    public $media_upload = null;

    public $media_duration = 15;

    public $media_bg_color = '#000000';

    public $media_color = '#FFFFFF';

    public $media_clock_location = 5;

    public $media_monitors = [];

    public function setMediaType(string $type)
    {
        $this->media_type = $type;
    }

    public function setSchedule(Schedule $schedule)
    {
        if (! $schedule->exists) {
            return;
        }
        $this->schedule = $schedule;
        $this->start_time = Carbon::parse($schedule->start_time)->format('H:i');
        $this->end_time = Carbon::parse($schedule->end_time)->format('H:i');
        $this->start = $schedule->start?->toDateString();
        $this->end = $schedule->end?->toDateString();
        $this->rrule = $schedule->rrule ?? '';
        $this->exceptionDates = $schedule->exceptions->map(fn (ScheduleException $e) => $e->exception_date->toDateString())->all();
        $this->disabled = $schedule->disabled;
        $this->media_type = $schedule->scheduleable_type === 'VI' ? 'video' : 'picture';

        $media = $schedule->scheduleable;
        $this->media_id = $media->id;
        $this->media_name = $media->name;
        $this->media_bg_color = $media->bg_color;
        $this->media_color = $media->color;
        if ($this->media_type === 'picture') {
            $this->media_duration = $media->duration;
        } else {
            $this->media_clock_location = $media->clock_location;
        }
        $this->media_monitors = $media->monitors->pluck('id')->all();
    }

    public function delete()
    {
        $media = $this->schedule->scheduleable;
        $logContext = [
            'id' => $this->schedule->id,
            'media_type' => $this->media_type,
            'scheduleable_id' => $this->schedule->scheduleable_id,
            'scheduleable_name' => $media?->name,
            'user' => auth()->id(),
        ];

        $schedule = $this->schedule;
        $realmId = $schedule->realm_id;
        $scheduleId = $schedule->id;

        // Deleting the media cascades to delete the Schedule, its
        // PictureSources and the stored files (see Picture/Video::booted()).
        $media?->delete();

        flash(__('The slide has been deleted'))->success();
        Log::channel('crud')->warning('Schedule (Slide) deleted', $logContext);

        event(new ItemUpdated($this->media_type === 'picture' ? 'ps' : 'vs', (object) [
            'id' => $scheduleId,
            'realm_id' => $realmId,
        ]));
    }

    public function store()
    {
        // Set default values if empty
        if (empty($this->start_time)) {
            $this->start_time = '00:00';
        }

        if (empty($this->end_time)) {
            $this->end_time = '23:59';
        }

        if (empty($this->start)) {
            $this->start = now()->yesterday()->toDateString();
        }

        if (empty($this->end)) {
            $this->end = '2100-12-31';
        }

        $validated = $this->validate();

        $this->validateMedia();

        if (! $this->schedule) {
            $this->schedule = new Schedule;
            $media = $this->media_type === 'picture' ? $this->createPicture() : $this->createVideo();
        } else {
            $media = $this->schedule->scheduleable;
            $this->media_type === 'picture' ? $this->updatePicture($media) : $this->updateVideo($media);
        }

        $this->schedule->fill([
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'start' => $validated['start'],
            'end' => $validated['end'],
            'rrule' => $this->rrule ?: null,
            'disabled' => $validated['disabled'] ?? false,
        ]);

        $this->schedule->scheduleable_type = $this->media_type === 'picture' ? 'PI' : 'VI'; // MorphMap code
        $this->schedule->scheduleable_id = $media->id;
        $this->schedule->realm_id = auth()->user()->realm_id;
        $this->schedule->user_id = auth()->id();
        $this->schedule->save();

        // Sync exception dates (RRULE EXDATE); irrelevant without an rrule.
        $exceptionDates = $this->rrule ? array_unique($this->exceptionDates) : [];
        $this->schedule->exceptions()->whereNotIn('exception_date', $exceptionDates)->delete();
        $existing = $this->schedule->exceptions()->pluck('exception_date')->map(fn ($d) => $d->toDateString())->all();
        foreach (array_diff($exceptionDates, $existing) as $date) {
            $this->schedule->exceptions()->create(['exception_date' => $date]);
        }

        event(new ItemUpdated($this->media_type === 'picture' ? 'ps' : 'vs', (object) [
            'id' => $this->schedule->id,
            'realm_id' => $this->schedule->realm_id,
        ]));
    }

    private function validateMedia(): void
    {
        $creating = ! $this->schedule;

        if ($this->media_type === 'picture') {
            $this->validate([
                'media_name' => 'required|max:191',
                'media_upload' => ($creating ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,gif|max:2048',
                'media_duration' => 'required|integer|min:1|max:120',
            ]);
        } else {
            $this->validate([
                'media_name' => 'required|max:191',
                'media_upload' => ($creating ? 'required' : 'nullable').'|file|mimes:mp4|max:25000',
                'media_clock_location' => 'required|integer|min:0|max:9',
            ]);
        }
    }

    private function createPicture(): Picture
    {
        /** @var UploadedFile $image */
        $image = $this->media_upload;

        $picture = new Picture;
        $picture->realm_id = auth()->user()->realm_id;
        $picture->user_id = auth()->id();
        $picture->name = $this->media_name;
        $picture->duration = $this->media_duration ?: 15;

        $name = Str::slug($this->media_name).'_'.rand(0, 32000000).'.'.$image->getClientOriginalExtension();
        $this->uploadOne($image, config('ads.pic_basepath'), 'public', $name);

        $size = getimagesize($image->getPathname());

        $palette = ColorThief::getPalette($image->get(), 3);
        $picture->bg_color = sprintf('#%02x%02x%02x', $palette[0][0], $palette[0][1], $palette[0][2]);
        $picture->color = sprintf('#%02x%02x%02x', $palette[2][0], $palette[2][1], $palette[2][2]);

        $picture->save();

        $picture->sources()->create([
            'path' => $name,
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
            'clock_location' => 5,
        ]);

        $this->syncMonitors($picture);

        return $picture;
    }

    private function createVideo(): Video
    {
        /** @var UploadedFile $vidFile */
        $vidFile = $this->media_upload;

        $video = new Video;
        $video->realm_id = auth()->user()->realm_id;
        $video->user_id = auth()->id();
        $video->name = $this->media_name;

        $name = Str::slug($this->media_name).'_'.rand(0, 32000000).'.'.$vidFile->getClientOriginalExtension();
        $this->uploadOne($vidFile, config('ads.vid_basepath'), 'public', $name);
        $video->path = $name;

        $video->bg_color = '#000000';
        $video->color = '#FFFFFF';

        $video->save();

        $this->syncMonitors($video);

        return $video;
    }

    private function updatePicture(Picture $picture): void
    {
        $picture->fill([
            'name' => $this->media_name,
            'duration' => $this->media_duration ?: 15,
            'bg_color' => $this->media_bg_color,
            'color' => $this->media_color,
        ]);
        $picture->save();

        $this->syncMonitors($picture);
    }

    private function updateVideo(Video $video): void
    {
        $video->fill([
            'name' => $this->media_name,
            'bg_color' => $this->media_bg_color,
            'color' => $this->media_color,
            'clock_location' => $this->media_clock_location,
        ]);
        $video->save();

        $this->syncMonitors($video);
    }

    private function syncMonitors(Picture|Video $media): void
    {
        $validMonitorIds = Monitor::ofRealm(auth()->user()->realm_id)
            ->whereIn('id', (array) $this->media_monitors)
            ->pluck('id');
        $media->monitors()->sync($validMonitorIds);
    }
}
