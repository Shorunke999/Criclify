<?php

namespace Modules\Circle\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CircleWalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Circle\Models\CircleWallet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

