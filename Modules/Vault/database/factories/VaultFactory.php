<?php

namespace Modules\Vault\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use  Modules\Vault\Enums\VaultStatusEnum;

class VaultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Vault\Models\Vault::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
          $interval = $this->faker->randomElement(['daily', 'weekly', 'monthly']);
        $duration = match ($interval) {
            'daily'   => 10,
            'weekly'  => 8,
            'monthly' => 6,
        };

        $totalAmount = $this->faker->numberBetween(50000, 500000);
        $intervalAmount = round($totalAmount / $duration, 2);

        return [
            'owner_id'        => \App\Models\User::factory(),
            'description'     => $this->faker->sentence,
            'total_amount'    => $totalAmount,
            'interval_amount' => $intervalAmount,
            'interval'        => $interval,
            'duration'        => $duration,
            'status'          => VaultStatusEnum::LOCKED,
            'maturity_date'   => now()->addMonths($duration),
            'start_date'      => now(),
            'oweing'           => false,
        ];
    }
}

