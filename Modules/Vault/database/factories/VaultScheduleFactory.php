<?php

namespace Modules\Vault\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Vault\Models\Vault;
use Modules\Vault\Enums\VaultScheduleStatusEnum;

class VaultScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Vault\Models\VaultSchedule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
         return [
            'vault_id'   => Vault::factory(),
            'amount_due'=> $this->faker->numberBetween(1000, 50000),
            'due_date'  => now()->addDays(7),
            'status'    => VaultScheduleStatusEnum::PENDING,
            'paid_at'   => null,
        ];
    }
}

