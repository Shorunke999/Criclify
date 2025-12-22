<?php

namespace Modules\Waitlist\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WaitlistQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Waitlist\Models\WaitlistQuestion::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'label' => $this->faker->sentence(4),
            'type' => 'text',
            'options' => null,
            'required' => false,
            'active' => true,
        ];
    }
}

