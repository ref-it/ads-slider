<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\HappyHour;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HappyHourController extends Controller
{
    /**
     * Show the form for creating a new Happy Hour for the given event.
     */
    public function create(Request $request, Event $event): View
    {
        $avUpdating = $request->routeIs('events.happyHours.avcreate');

        if (! $avUpdating) {
            $this->authorize('create', [HappyHour::class, $event]);
        } else {
            $this->authorize('createAsManager', [HappyHour::class, $event]);
        }

        return view('events.happy-hours.create', compact('event', 'avUpdating'));
    }

    /**
     * Show the form for editing the specified Happy Hour.
     */
    public function edit(Request $request, Event $event, HappyHour $happyHour): View
    {
        abort_if($happyHour->event_id !== $event->id, 404);

        $avUpdating = $request->routeIs('events.happyHours.avedit');

        if (! $avUpdating) {
            $this->authorize('update', $happyHour);
        } else {
            $this->authorize('updateAsManager', [$happyHour, $event]);
        }

        return view('events.happy-hours.edit', compact('event', 'happyHour', 'avUpdating'));
    }
}
