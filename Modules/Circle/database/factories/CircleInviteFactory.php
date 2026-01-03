<?php

namespace Modules\Circle\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Circle\Models\Circle;
use App\Models\User;
use Illuminate\Support\Str;

class CircleInviteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Circle\Models\CircleInvite::class;

    /**
     * Define the model's default state.
     */
     public function definition(): array
    {
        return [
            'circle_id' => Circle::factory(),
            'inviter_id' => User::factory(),
            'invitee_id' => null,
            'contact' => $this->faker->safeEmail(),
            'token' => Str::random(32),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}

