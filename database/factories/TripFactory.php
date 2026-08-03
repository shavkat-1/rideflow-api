<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'passenger_id' => User::factory(),
            'driver_id' => null,

            'pricing_type' => $this->faker->randomElement([
                'fixed',
                'calculated',
            ]),

            'estimated_price' => $this->faker->numberBetween(100, 5000),
            'final_price' => null,

            'status' => 'pending',
        ];
    }
}
