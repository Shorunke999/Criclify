<?php

namespace Modules\Circle\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Circle\Enums\CircleIntervalEnum;
use Modules\Circle\Enums\CircleStatusEnum;
use Modules\Circle\Enums\PositionSelectionMethodEnum;

class CircleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Circle\Models\Circle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
         return [
            'description' => $this->faker->sentence(),
            'code' => strtoupper($this->faker->bothify('CIR###')),
            'creator_id' => User::factory(),
            'amount' => $this->faker->numberBetween(500, 5000),
            'interval' => CircleIntervalEnum::Weekly,
            'select_position_method' => PositionSelectionMethodEnum::Sequence,
            'status' => CircleStatusEnum::Active,
            'limit' => $this->faker->numberBetween(3, 10),
            'start_date' => now()->subDay(),
            'end_date' => null,
            'multiple_position' => false,
        ];
    }

      /* -------- States -------- */

    public function randomPosition(): static
    {
        return $this->state(fn () => [
            'select_position_method' => PositionSelectionMethodEnum::Random,
        ]);
    }
}

