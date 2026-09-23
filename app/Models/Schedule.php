<?php

namespace App\Models;

use App\Support\Recurrence\RecurrenceOccurrences;
use App\Support\Recurrence\WeekdayMaskConverter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
        'disabled' => 'boolean',
    ];

    protected $appends = ['real_start_date', 'real_end_date'];

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('disabled', 0)
            ->where(function (Builder $q) {
                $now = today()->toDateString();
                $q->where(function (Builder $sub) use ($now) {
                    $sub->whereDate('start', '<=', $now)->orWhereNull('start');
                })->where(function (Builder $sub) use ($now) {
                    $sub->whereDate('end', '>=', $now)->orWhereNull('end');
                });
            });
    }

    public function scheduleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    /**
     * A human-readable summary of the recurrence, e.g. "Monday, Wednesday"
     * for both the legacy digit mask and an rrule that reduces to a plain
     * weekly pattern; a generic fallback for anything else (custom
     * interval/monthly/exceptions - not worth spelling out in a list view).
     * Callers prefix this with a recurrence icon rather than a text label.
     */
    public function getRecurrenceDescriptionAttribute(): ?string
    {
        if ($this->rrule) {
            $digits = WeekdayMaskConverter::fromRrule($this->rrule);

            return $digits !== null ? self::describeWeekdayDigits($digits) : __('Repeats');
        }

        if ($this->repeat) {
            return self::describeWeekdayDigits($this->repeat);
        }

        return null;
    }

    private static function describeWeekdayDigits(string $digits): string
    {
        return implode(', ', array_map(function ($el) {
            $n = $el === '7' ? 0 : (int) $el;

            return Carbon::now()->next($n)->dayName;
        }, str_split($digits)));
    }

    /**
     * Compute the day in format Y-m-d H:i:s of the next occurrence of this event
     *
     * @return mixed|string
     */
    public function getRealStartDateAttribute(): ?string
    {
        return self::calculateStartDateTime(
            $this->start?->toDateString(),
            $this->end?->toDateString(),
            $this->start_time,
            $this->end_time,
            $this->repeat,
            null,
            $this->rrule,
            $this->exceptionDates()
        );
    }

    /**
     * Get the event end date as Y-m-d H:i:s
     */
    public function getRealEndDateAttribute(): ?string
    {
        return self::calculateEndDateTime(
            $this->start?->toDateString(),
            $this->end?->toDateString(),
            $this->start_time,
            $this->end_time,
            $this->repeat,
            null,
            $this->rrule,
            $this->exceptionDates()
        );
    }

    /**
     * @return string[] Exception dates as 'Y-m-d' strings.
     */
    private function exceptionDates(): array
    {
        if (! $this->rrule) {
            return [];
        }

        return $this->exceptions->map(fn (ScheduleException $e) => $e->exception_date->toDateString())->all();
    }

    /* -------------------------------------------------------------------------- */
    /*                                CALCULATION */
    /* -------------------------------------------------------------------------- */

    public static function calculateDuration(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null, ?string $rrule = null, array $exceptionDates = []): ?string
    {
        $startDate = self::calculateStartDateTime($start, $end, $start_time, $end_time, $repeat, $now, $rrule, $exceptionDates);
        if (! $startDate) {
            return null;
        }

        $endDate = self::calculateEndCarbon($start, $end, $start_time, $end_time, $repeat, $now, $rrule, $exceptionDates);
        if (! $endDate) {
            return null;
        }

        return $endDate->diffForHumans($startDate, [
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            'parts' => 6,
            'join' => true,
            'short' => false,
        ]);
    }

    /**
     * Returns a string in the format 'Y-m-d H:i:s' of the next occurrence of this event
     *
     * @param  string[]  $exceptionDates  'Y-m-d' dates to skip, only used together with $rrule
     */
    public static function calculateStartDateTime(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null, ?string $rrule = null, array $exceptionDates = []): ?string
    {
        if (! $start_time) {
            return null;
        }

        if (! $repeat && ! $rrule) {
            if (! $start) {
                return null;
            }
            if ($start_time) {
                return Carbon::parse($start.'T'.$start_time)->format('Y-m-d H:i:s');
            }

            return $start;
        }

        if (! $end_time) {
            return null;
        }

        if (! $now) {
            $now = Carbon::now();
        } else {
            $now = $now->clone();
        }

        $dtStart = $rrule ? self::rruleAnchor($rrule, $start) : null;

        // check if it is starting today
        if (self::repeatsOn($repeat, $rrule, $exceptionDates, $now, $dtStart)) {
            if ((new Carbon($start_time))->greaterThan(new Carbon($end_time))) {
                // the event runs overnight

                // If the event would run yesterday too, and it's not over yet
                if (self::repeatsOn($repeat, $rrule, $exceptionDates, $now->copy()->subDay(), $dtStart) && $now->format('His.u') < (new Carbon($end_time))->format('His.u')) {
                    return $now->subDays(1)->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
                }

                return $now->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
            } else {
                return $now->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
            }
        } elseif (self::repeatsOn($repeat, $rrule, $exceptionDates, $now->copy()->subDay(), $dtStart)) {
            // The event started yesterday, it might still be running today
            if ((new Carbon($start_time))->greaterThan(new Carbon($end_time))) {
                // the event runs overnight

                // If the event would run yesterday too, and it's not over yet

                if ($now->format('His.u') < (new Carbon($end_time))->format('His.u')) {
                    return $now->subDays(1)->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
                }
            }
        }

        // Not happening today nor yesterday, look for the next occurrence within the next 7 days
        $nextOccurrenceDate = null;
        for ($i = 1; $i <= 7; $i++) {
            $candidate = $now->copy()->addDays($i);
            if (self::repeatsOn($repeat, $rrule, $exceptionDates, $candidate, $dtStart)) {
                $nextOccurrenceDate = $candidate;
                break;
            }
        }
        abort_if($nextOccurrenceDate === null, 500);

        return $nextOccurrenceDate->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
    }

    public static function calculateEndCarbon(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null, ?string $rrule = null, array $exceptionDates = []): ?Carbon
    {
        $end = self::calculateEndDateTime($start, $end, $start_time, $end_time, $repeat, $now, $rrule, $exceptionDates);
        if (! $end) {
            return null;
        }

        return Carbon::parse($end);
    }

    public static function calculateEndDateTime(?string $start, ?string $end, string $start_time, string $end_time, ?string $repeat, ?Carbon $now = null, ?string $rrule = null, array $exceptionDates = []): ?string
    {
        if (! $end_time) {
            return null;
        }

        if (! $repeat && ! $rrule) {
            if ($end) {
                if ($end_time) {
                    return Carbon::parse($end.'T'.$end_time)->setSecond(59)->format('Y-m-d H:i:s');
                }

                return Carbon::parse($end);
            }

            return null;
        }

        if (! $start_time) {
            return null;
        }

        if (! $now) {
            $now = Carbon::now();
        } else {
            $now = $now->clone();
        }

        $startDate = self::calculateStartDateTime($start, $end, $start_time, $end_time, $repeat, $now, $rrule, $exceptionDates);
        if (! $startDate) {
            return null;
        }

        $endDate = Carbon::parse($startDate)->setTimeFromTimeString($end_time);
        if ($endDate->isBefore($startDate)) {
            $endDate->addDay();
        }

        return $endDate->setSecond(59)->format('Y-m-d H:i:s');
    }

    /**
     * Whether the recurrence occurs on the given date: the legacy weekday
     * digit mask when there's no $rrule, otherwise the real RRULE (minus
     * any exception dates).
     *
     * @param  string[]  $exceptionDates  'Y-m-d' dates to skip
     */
    private static function repeatsOn(?string $repeat, ?string $rrule, array $exceptionDates, Carbon $date, ?Carbon $dtStart): bool
    {
        if ($rrule) {
            if (in_array($date->toDateString(), $exceptionDates, true)) {
                return false;
            }

            return RecurrenceOccurrences::occursOn($rrule, $dtStart, $date);
        }

        return $repeat !== null && str_contains($repeat, (string) self::getDayOfWeek($date));
    }

    /**
     * The RRULE's DTSTART anchor. Sabre's RRuleIterator always treats
     * DTSTART as an occurrence, even when it doesn't itself match the
     * pattern (e.g. a Wednesday anchor with BYDAY=TH) - fine for
     * interval/monthly/yearly rules, where the anchor's date genuinely
     * fixes the pattern's phase and $start is expected to be set. For a
     * plain weekly BYDAY pattern (interval 1, the shape WeekdayMaskConverter
     * produces) the anchor doesn't affect which dates match, so an
     * unrelated $start must never leak in as a spurious extra occurrence -
     * a fixed, arbitrary Monday is used instead, regardless of $start.
     */
    private static function rruleAnchor(string $rrule, ?string $start): Carbon
    {
        $needsExplicitAnchor = preg_match('/FREQ=(MONTHLY|YEARLY)\b/', $rrule) === 1
            || preg_match('/INTERVAL=(?!1\b)\d+/', $rrule) === 1;

        if ($needsExplicitAnchor && $start) {
            return Carbon::parse($start);
        }

        return Carbon::parse('1970-01-05'); // arbitrary Monday
    }

    private static function getDayOfWeek(Carbon $date): int
    {
        $dow = $date->dayOfWeek; // 0 Su, 6 Saturday
        // We want: Mo: 1, Su: 7
        if ($dow === 0) {
            $dow = 7;
        }

        return $dow;
    }
}
