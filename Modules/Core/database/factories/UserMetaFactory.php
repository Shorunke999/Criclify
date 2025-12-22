<?php

namespace Modules\Core\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMetaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Core\Models\UserMeta::class;

    /**
     * Define the model's default state.
     */

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'referral_code' => null,
            'referral_count' => 0,
        ];
    }
}

