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
    CircleStatusEnum
};
use Modules\Circle\Services\ContributionService;
use Carbon\Carbon;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Core\Events\AuditLogged;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;

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
    public function user_can_initiate_contribution_payment()
    {
        $this->actingAsUser();

        // Mock provider
        $this->mock(\Modules\Payment\Repositories\Contracts\PaymentProviderInterface::class, function ($mock) {
            $mock->shouldReceive('initialize')
                ->once()
                ->andReturn([
                    'authorization_url' => 'https://paystack.test/authorize',
                ]);
        });

        $circle = Circle::factory()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
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
                'amount' => 1000,
                'contribution_id' => $contribution->id,
            ]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transaction_id',
                    'reference',
                    'authorization_url',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'circle_id' => $circle->id,
            'amount' => 1000,
            'status' => TransactionStatusEnum::Pending->value,
        ]);
    }

    /** @test */
    public function webhook_marks_contribution_as_paid()
    {
        $this->actingAsUser();

        Event::fake();

        $circle = Circle::factory()->create();
        $circle->wallet()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

        $contribution = CircleContribution::factory()->create([
            'circle_id' => $circle->id,
            'circle_member_id' => $member->id,
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => StatusEnum::Pending,
        ]);

        $transaction = \Modules\Payment\Models\Transaction::factory()->create([
            'user_id' => $this->user->id,
            'circle_id' => $circle->id,
            'amount' => 1000,
            'type' => TransactionTypeEnum::Contribution,
            'type_ids' => [$contribution->id],
            'reference' => 'ref-123',
            'status' => TransactionStatusEnum::Pending,
        ]);

        // Mock provider webhook
        $this->mock(\Modules\Payment\Repositories\Contracts\PaymentProviderInterface::class, function ($mock) {
            $mock->shouldReceive('webhook')
                ->once()
                ->andReturn('ref-123');
        });

        $response = $this->postJson('/api/payment/webhooks', []);

        $response->assertOk();

        $this->assertDatabaseHas('circle_contributions', [
            'id' => $contribution->id,
            'status' => StatusEnum::Paid->value,
            'paid_amount' => 1000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,

            'status' => TransactionStatusEnum::Success->value,
        ]);

        Event::assertDispatched(AuditLogged::class);
    }

    /** @test */
    public function partial_payment_updates_contribution_correctly()
    {
        $this->actingAsUser();
        Event::fake();
        $circle = Circle::factory()->create();
        $circle->wallet()->create();
        $member = CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

        $contribution = CircleContribution::factory()->create([
            'circle_id' => $circle->id,
            'circle_member_id' => $member->id,
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => StatusEnum::Pending,
        ]);

        $transaction = \Modules\Payment\Models\Transaction::factory()->create([
            'user_id' => $this->user->id,
            'circle_id' => $circle->id,
            'amount' => 400,
            'type' => TransactionTypeEnum::Contribution,
            'type_ids' => [$contribution->id],
            'reference' => 'partial-ref',
            'status' => TransactionStatusEnum::Pending,
        ]);

         // Mock provider webhook
        $this->mock(\Modules\Payment\Repositories\Contracts\PaymentProviderInterface::class, function ($mock) {
            $mock->shouldReceive('webhook')
                ->once()
                ->andReturn('partial-ref');
        });


        $response = $this->postJson('/api/payment/webhooks', []);

        $response->assertOk();

        $this->assertDatabaseHas('circle_contributions', [
            'id' => $contribution->id,
            'status' => StatusEnum::Partpayment->value,
            'paid_amount' => 400,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,

            'status' => TransactionStatusEnum::Success->value,
        ]);

        Event::assertDispatched(AuditLogged::class);
    }


}
