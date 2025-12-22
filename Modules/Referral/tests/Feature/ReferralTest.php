<?php

namespace Modules\Referral\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\UserMeta;
use Laravel\Sanctum\Sanctum;

class ReferralTest extends TestCase
{

    use RefreshDatabase;

     protected function setUp(): void
    {
        parent::setUp();

        // Run module migrations
        $this->artisan('migrate',['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }
    public function test_authenticated_user_can_generate_referral_code()
    {
        $user = User::factory()->create();
        UserMeta::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/referral/code');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['code'],
            ]);

        $this->assertDatabaseHas('user_metas', [
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_generate_referral_code()
    {
        $response = $this->getJson('/api/referral/code');

        $response->assertStatus(401);
    }
    public function test_admin_can_view_referral_leaderboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        UserMeta::factory()->count(3)->create([
            'referral_count' => fake()->numberBetween(1, 20),
        ]);

        $response = $this->getJson('/api/referral/leaderboard');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'user_id',
                        'referral_count',
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_view_leaderboard()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/referral/leaderboard')
            ->assertStatus(403);
    }

}
