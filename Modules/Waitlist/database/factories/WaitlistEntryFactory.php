<?php

namespace Modules\Waitlist\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WaitlistEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Waitlist\Models\WaitlistEntry::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'referral_code' => null,
            'referral_count' => 0,
            'survey_data' => null,
        ];
    }

}

