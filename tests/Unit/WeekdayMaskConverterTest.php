<?php

namespace Tests\Unit;

use App\Support\Recurrence\WeekdayMaskConverter;
use Tests\TestCase;

class WeekdayMaskConverterTest extends TestCase
{
    public function test_to_rrule_converts_digits_to_byday_in_canonical_order(): void
    {
        $this->assertSame('FREQ=WEEKLY;BYDAY=MO,WE,FR', WeekdayMaskConverter::toRrule('531'));
    }

    public function test_to_rrule_single_day(): void
    {
        $this->assertSame('FREQ=WEEKLY;BYDAY=SU', WeekdayMaskConverter::toRrule('7'));
    }

    public function test_to_rrule_returns_null_for_blank_input(): void
    {
        $this->assertNull(WeekdayMaskConverter::toRrule(null));
        $this->assertNull(WeekdayMaskConverter::toRrule(''));
    }

    public function test_from_rrule_reduces_simple_weekly_byday_to_digits(): void
    {
        $this->assertSame('135', WeekdayMaskConverter::fromRrule('FREQ=WEEKLY;BYDAY=MO,WE,FR'));
    }

    public function test_from_rrule_returns_null_for_interval(): void
    {
        $this->assertNull(WeekdayMaskConverter::fromRrule('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO'));
    }

    public function test_from_rrule_returns_null_for_non_weekly_frequency(): void
    {
        $this->assertNull(WeekdayMaskConverter::fromRrule('FREQ=MONTHLY;BYDAY=MO'));
    }

    public function test_from_rrule_returns_null_for_until(): void
    {
        $this->assertNull(WeekdayMaskConverter::fromRrule('FREQ=WEEKLY;BYDAY=MO;UNTIL=20261231T000000Z'));
    }

    public function test_round_trip_is_stable(): void
    {
        $rrule = WeekdayMaskConverter::toRrule('246');
        $this->assertSame('246', WeekdayMaskConverter::fromRrule($rrule));
    }
}
