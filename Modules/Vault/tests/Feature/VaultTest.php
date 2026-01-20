<?php

namespace Modules\Vault\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Vault\Models\Vault;
use Modules\Vault\Models\VaultSchedule;
use Modules\Vault\Enums\VaultStatusEnum;
use Modules\Vault\Enums\VaultScheduleStatusEnum;
use App\Models\User;

class VaultTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        $this->artisan('db:seed');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // ensure wallet exists

        $this->user->wallet()->create([
            'balance' => 1000000,
            'type' => 'user'
        ]);
    }

        /** @test */
    public function user_can_create_a_vault()
    {
        $payload = [
            'description'   => 'New Savings Goal',
            'total_amount'  => 100000,
            'interval'      => 'monthly',
            'maturity_date' => now()->addMonths(5)->toDateString(),
        ];

        $response = $this->postJson('/api/vaults', $payload);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Goal created successfully',
            ]);

        $this->assertDatabaseHas('vaults', [
            'owner_id' => $this->user->id,
            'total_amount' => 100000,
        ]);

        $this->assertDatabaseCount('vault_schedules', 5);
    }

        /** @test */
    public function user_can_list_their_vaults()
    {
        Vault::factory()->count(3)->create([
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/vaults');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'total_amount', 'status'],
                    ],
                ]

            ]);
    }

        /** @test */
    public function user_can_view_vault_details()
    {
        $vault = Vault::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        VaultSchedule::factory()->count(3)->create([
            'vault_id' => $vault->id,
            'amount_due' => $vault->interval_amount
        ]);

        $response = $this->getJson("/api/vaults/{$vault->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'vault',
                    'transaction_details',
                ],
            ]);
    }

        /** @test */
    public function user_can_pay_next_pending_vault_schedule()
    {
        $vault = Vault::factory()->create([
            'owner_id' => $this->user->id,
            'status' => VaultStatusEnum::LOCKED,
        ]);

        VaultSchedule::factory()->create([
            'vault_id' => $vault->id,
            'status' => VaultScheduleStatusEnum::PENDING,
            'amount_due' => 10000,
            'due_date' => now(),
        ]);

        $response = $this->postJson("/api/vaults/{$vault->id}/pay");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Goal payment successful',
            ]);

        $this->assertDatabaseHas('vault_schedules', [
            'vault_id' => $vault->id,
            'status' => VaultScheduleStatusEnum::PAID,
        ]);
    }

        /** @test */
    public function completed_and_matured_vaults_are_unlocked()
    {
        $vault = Vault::factory()->create([
            'status' => VaultStatusEnum::COMPLETED,
            'maturity_date' => now()->subDay(),
        ]);

        app(\Modules\Vault\Services\VaultService::class)
            ->unlockCompletedVault();

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'status' => VaultStatusEnum::UNLOCKED,
        ]);
    }

        /** @test */
    public function unlocked_vault_can_be_disbursed()
    {
        $vault = Vault::factory()->create([
            'owner_id' => $this->user->id,
            'status' => VaultStatusEnum::UNLOCKED,
            'total_amount' => 50000,
        ]);

        $response = $this->postJson("/api/vaults/{$vault->id}/disburse");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Goal disbursement successful',
            ]);

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'status' => VaultStatusEnum::DISBURSED,
        ]);
    }




}
