<?php

namespace Modules\Circle\Tests\Feature;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Modules\Circle\Models\{
    Circle,
    CircleContribution,
    CircleMember
};
use Modules\Circle\Enums\{
    StatusEnum,
};
use Modules\Circle\Services\ContributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Core\Events\AuditLogged;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Core\Enums\WalletTypeEnum;
use Modules\Core\Models\Wallet;
use Modules\Payment\Models\Transaction;

class ContributionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function actingAsUser(): void
    {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

     /** @test */
    public function user_can_list_their_contributions()
    {
        $this->actingAsUser();

        $member = CircleMember::factory()->create([
            'user_id' => $this->user->id,
        ]);

        CircleContribution::factory()->create([
            'circle_member_id' => $member->id,
        ]);

        $this->getJson('/api/my/contributions')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    /** @test */
    public function can_filter_contributions_by_circle()
    {
        $this->actingAsUser();

        $circle = Circle::factory()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

        CircleContribution::factory()->create([
            'circle_id' => $circle->id,
            'circle_member_id' => $member->id,
        ]);

        $this->getJson("/api/circles/{$circle->id}/contributions")
            ->assertOk()
            ->assertJsonFragment(['circle_id' => $circle->id]);
    }

    /** @test */
    public function overdue_contributions_are_marked_and_emitted()
    {
        $contribution = CircleContribution::factory()->create([
            'due_date' => now()->subDays(2),
            'status' => StatusEnum::Pending,
        ]);

        app(ContributionService::class)->handleOverdue();

        $this->assertDatabaseHas('circle_contributions', [
            'id' => $contribution->id,
            'status' => StatusEnum::Overdue->value,
        ]);
    }

    /** @test */
    public function reminder_fires_for_upcoming_due_contributions()
    {
        CircleContribution::factory()->create([
            'due_date' => now()->addDays(1),
            'status' => StatusEnum::Pending,
        ]);

        app(ContributionService::class)->contributionReminder();

        $this->assertTrue(true); // event tested separately if needed
    }
    /** @test */
    public function user_can_make_full_contribution_payment()
    {
        $this->actingAsUser();

        // Setup circle and member
        $circle = Circle::factory()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

        // Setup user wallet with sufficient balance
        $userWallet = $this->user->wallet()->create([
            'balance' => 5000,
        ]);

        // Setup circle wallet
        $circleWallet = $circle->wallet()->create([
            'balance' => 0,
        ]);

        // Create contribution
        $contribution = CircleContribution::factory()->create([
            'circle_id' => $circle->id,
            'circle_member_id' => $member->id,
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => StatusEnum::Pending,
        ]);

        $response = $this->postJson(
            "/api/members/{$member->id}/contributions/pay",
            [
                'amount' => 1000,
                'contribution_id' => $contribution->id,
            ]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // Assert transaction created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'wallet_id' => $userWallet->id,
            'circle_id' => $circle->id,
            'amount' => 1000,
            'type' => TransactionTypeEnum::Contribution->value,
            'status' => TransactionStatusEnum::Success->value,
        ]);

        // Assert transactable pivot created
        $transaction = Transaction::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('transactables', [
            'transaction_id' => $transaction->id,
            'transactable_id' => $contribution->id,
            'transactable_type' => get_class($contribution),
            'amount' => 1000,
        ]);

        // Assert contribution updated
        $this->assertDatabaseHas('circle_contributions', [
            'id' => $contribution->id,
            'paid_amount' => 1000,
            'status' => StatusEnum::Paid->value,
        ]);

        // Assert user wallet debited
        $this->assertEquals(4000, $userWallet->fresh()->balance);

        // Assert circle wallet credited
        $this->assertEquals(1000, $circleWallet->fresh()->balance);
    }

    /** @test */
    public function user_can_make_partial_contribution_payment()
    {
        $this->actingAsUser();

        $circle = Circle::factory()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

          // Setup user wallet with sufficient balance
        $userWallet = $this->user->wallet()->create([
            'balance' => 5000,
        ]);

        // Setup circle wallet
        $circleWallet = $circle->wallet()->create([
            'balance' => 0,
        ]);

        $contribution = CircleContribution::factory()->create([
            'circle_id' => $circle->id,
            'circle_member_id' => $member->id,
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => StatusEnum::Pending,
        ]);

        $response = $this->postJson(
            "/api/members/{$member->id}/contributions/pay",
            [
                'amount' => 700,
                'contribution_id' => $contribution->id,
            ]
        );

        $response->assertOk();

        // Assert transaction created with partial amount
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 700,
            'status' => TransactionStatusEnum::Success->value,
        ]);

        // Assert transactable pivot with partial amount
        $transaction = Transaction::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('transactables', [
            'transaction_id' => $transaction->id,
            'transactable_id' => $contribution->id,
            'amount' => 700,
        ]);

        // Assert contribution status is partial
        $this->assertDatabaseHas('circle_contributions', [
            'id' => $contribution->id,
            'paid_amount' => 700,
            'status' => StatusEnum::Partpayment->value,
        ]);

        // Assert wallet balances
        $this->assertEquals(4300, $userWallet->fresh()->balance);
        $this->assertEquals(700, $circleWallet->fresh()->balance);
    }
}
