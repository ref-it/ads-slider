<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Schedule;
use App\Models\Video;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class SlideController extends Controller
{
    /**
     * @return mixed
     *
     * @deprecated
     */
    public static function getScheduledPictures()
    {
        return Schedule::where('scheduleable_type', 'PI')->active()->with('scheduleable.monitors:id');
    }

    /**
     * @return mixed
     *
     * @deprecated
     */
    public static function getScheduledVideos()
    {
        return Schedule::where('scheduleable_type', 'VI')->active()->with('scheduleable.monitors:id');
    }

    public static function getScheduledPicturesOnMonitor(Monitor $m): ?Collection
    {
        if ($m->show_pictures === 0) {
            return null;
        }

        $query = Schedule::where('scheduleable_type', 'PI')->active()->whereHasMorph('scheduleable', [Picture::class], function (Builder $query) use ($m) {
            $query->where('realm_id', $m->realm_id);
            // Check for monitors inside the morph constraint where we know it's a Picture
            $query->where(function ($q) use ($m) {
                $q->whereDoesntHave('monitors')
                    ->orWhereHas('monitors', function ($mq) use ($m) {
                        $mq->where('monitors.id', $m->id);
                    });
            });
        })->with(['scheduleable' => function ($q) {
            $q->select('id', 'bg_color', 'color', 'realm_id', 'duration')->with('sources');
        }]);

        $results = $query->get();

        if (isset($m->stats['screen']['availWidth']) && isset($m->stats['screen']['availHeight'])) {
            $w = intval($m->stats['screen']['availWidth']);
            $h = intval($m->stats['screen']['availHeight']);
            foreach ($results as $slide) {
                if ($slide->scheduleable instanceof Picture) {
                    $slide->scheduleable->setMonitorDimensions($w, $h);
                    $slide->scheduleable->makeHidden('sources');
                }
            }
        }

        return $results;
    }

    public static function getScheduledVideosOnMonitor(Monitor $m): ?Builder
    {
        if ($m->show_videos === 0) {
            return null;
        }

        return Schedule::where('scheduleable_type', 'VI')->active()->whereHasMorph('scheduleable', [Video::class], function (Builder $query) use ($m) {
            $query->where('realm_id', $m->realm_id);
            // Check for monitors inside the morph constraint where we know it's a Video
            $query->where(function ($q) use ($m) {
                $q->whereDoesntHave('monitors')
                    ->orWhereHas('monitors', function ($mq) use ($m) {
                        $mq->where('monitors.id', $m->id);
                    });
            });
        })->with('scheduleable');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Schedule::class);

        $slides = Schedule::whereIn('scheduleable_type', ['PI', 'VI'])
            ->orderBy('updated_at', 'DESC')
            ->whereHasMorph('scheduleable', [Picture::class, Video::class], function (Builder $query) {
                $query->where('realm_id', auth()->user()->realm_id);
            })
            ->with(['user:id,name', 'scheduleable'])
            ->paginate(10);

        return view('slides.index', compact('slides'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create(?string $type = null): View
    {
        $this->authorize('create', Schedule::class);

        return view('slides.create', ['type' => $type]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $slide): View
    {
        $this->authorize('update', $slide);

        return view('slides.edit', compact('slide'));
    }
}
