<?php

namespace Modules\Circle\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Circle\Models\Circle;

class CircleMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Circle\Models\CircleMember::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
         return [
            'circle_id' => Circle::factory(),
            'user_id' => User::factory(),
            'position' => $this->faker->numberBetween(1, 10),
            'no_of_times' => 1,
            'paid_status' => 'pending',
        ];
    }
}

