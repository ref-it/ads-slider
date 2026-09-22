<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ScheduleException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleException>
 */
class ScheduleExceptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'exception_date' => $this->faker->dateTimeBetween('now', '+1 years')->format('Y-m-d'),
        ];
    }
}
