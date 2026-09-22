<?php

namespace Database\Factories;

use App\Models\Canteen;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Canteen>
 */
class CanteenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mensa '.$this->faker->city(),
            'external_id' => $this->faker->numberBetween(1, 100),
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
