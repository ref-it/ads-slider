<?php

use App\Support\Recurrence\WeekdayMaskConverter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfills the new `rrule` column from the legacy `repeat` weekday
     * mask, for every schedule row that has one. `repeat` itself is left
     * untouched (non-destructive, rollback-safe) - it's dropped separately,
     * in a later deploy, once the new column has proven itself.
     */
    public function up(): void
    {
        DB::table('schedules')
            ->whereNotNull('repeat')
            ->orderBy('id')
            ->chunk(500, function ($schedules) {
                foreach ($schedules as $schedule) {
                    $rrule = WeekdayMaskConverter::toRrule($schedule->repeat);
                    if ($rrule === null) {
                        continue;
                    }

                    DB::table('schedules')->where('id', $schedule->id)->update(['rrule' => $rrule]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('schedules')->whereNotNull('rrule')->update(['rrule' => null]);
    }
};
