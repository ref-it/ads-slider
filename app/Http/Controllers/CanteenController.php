<?php

namespace App\Http\Controllers;

use App\Models\Canteen;
use App\Models\Monitor;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class CanteenController extends Controller
{
    /**
     * The canteens whose menu should currently be considered for display on
     * the given monitor: their schedule is active (date window) and the
     * canteen is explicitly attached to this monitor (no "shown everywhere"
     * fallback, unlike Picture).
     */
    public static function getScheduledCanteensOnMonitor(Monitor $m): ?Collection
    {
        if (! $m->show_canteens) {
            return null;
        }

        return Schedule::where('scheduleable_type', 'CA')->active()
            ->whereHasMorph('scheduleable', [Canteen::class], function (Builder $query) use ($m) {
                $query->where('realm_id', $m->realm_id)
                    ->whereHas('monitors', function ($mq) use ($m) {
                        $mq->where('monitors.id', $m->id);
                    });
            })
            ->with(['scheduleable' => function ($q) {
                $q->select('id', 'name');
            }])
            ->get();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Schedule::class);

        $canteens = Schedule::where('scheduleable_type', 'CA')->orderBy('updated_at', 'DESC')
            ->whereHasMorph('scheduleable', [Canteen::class], function (Builder $query) {
                $query->where('realm_id', auth()->user()->realm_id);
            })
            ->with(['user:id,name', 'scheduleable:id,name,realm_id'])
            ->paginate(10);

        return view('canteens.index', compact('canteens'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Schedule::class);

        return view('canteens.create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Canteen $canteen): View
    {
        $this->authorize('update', $canteen->schedule);

        return view('canteens.edit', compact('canteen'));
    }
}
