<?php

namespace Modules\Circle\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Models\Circle;
use Modules\Circle\Models\CircleMember;

class CircleContributionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Circle\Models\CircleContribution::class;

    /**
     * Define the model's default state.
     */
     public function definition(): array
    {
        return [
            'circle_id' => Circle::factory(),
            'circle_member_id' => CircleMember::factory(),
            'amount' => $this->faker->numberBetween(500, 5000),
            'due_date' => now(),
            'status' => StatusEnum::Pending,
        ];
    }

    /* -------- States -------- */

    public function paid(): static
    {
        return $this->state(fn () => [
            'paid_amount' => $this->faker->numberBetween(500, 5000),
            'status' => StatusEnum::Paid,
        ]);
    }

    public function partPayment(): static
    {
        return $this->state(fn () => [
            'paid_amount' => 200,
            'status' => StatusEnum::Partpayment,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(2),
            'status' => StatusEnum::Overdue,
        ]);
    }
}

