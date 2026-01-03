<?php

namespace Modules\Circle\Tests\Feature;

use App\Enums\AuditAction;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Circle\Enums\CircleIntervalEnum;
use Modules\Circle\Models\Circle;
use Modules\Circle\Models\CircleInvite;
use Illuminate\Support\Str;
use Modules\Circle\Enums\PositionSelectionMethodEnum;
use Modules\Circle\Listeners\CreateContributionsListener;
use Modules\Circle\Models\CircleMember;
use Illuminate\Support\Facades\Event;
use Modules\Circle\Enums\CircleStatusEnum;
use Modules\Circle\Enums\InviteStatusEnum;
use Modules\Circle\Events\AcceptInviteEvent;
use Modules\Circle\Events\CreateContributionsEvent;
use Modules\Circle\Events\SendCircleInvite;
use Modules\Core\Events\AuditLogged;

class CircleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function actingAsUser(): void
    {
        $this->user = User::factory()->create([
            'kyc_verified_at' => now(),
        ]);
        Sanctum::actingAs($this->user);
    }

    public function user_can_create_circle()
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/circles', [
            'amount' => 1000,
            'interval' => CircleIntervalEnum::Weekly->value,
            'limit' => 5,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('circles', [
            'creator_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_join_circle()
    {
        $this->actingAsUser();

        $circle = Circle::factory()->create(['limit' => 5]);

        $this->postJson("/api/circles/{$circle->id}/join")
            ->assertOk();

        $this->assertDatabaseHas('circle_members', [
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function member_can_invite_user()
    {
        $this->actingAsUser();
        Event::fake();

        $circle = Circle::factory()->create();
        $circle->members()->create([
            'user_id' => $this->user->id,
            'position' => 1,
        ]);

        $response = $this->postJson("/api/circles/{$circle->id}/invite", [
            'emails' => ['invitee@test.com'],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('circle_invites', [
            'circle_id' => $circle->id,
            'contact' => 'invitee@test.com',
        ]);

        Event::assertDispatched(SendCircleInvite::class);
    }


    /** @test */
    public function invited_user_can_accept_invite()
    {
        $this->actingAsUser();
        Event::fake();

        $circle = Circle::factory()->create();

        $invite = CircleInvite::factory()->create([
            'circle_id' => $circle->id,
            'token' => Str::random(32),
            'status' => InviteStatusEnum::Pending,
        ]);

        $this->postJson("/api/circles/invite/{$invite->token}/accept")
            ->assertOk();

        $this->assertDatabaseHas('circle_members', [
            'circle_id' => $circle->id,
            'user_id' => $this->user->id,
        ]);

        Event::assertDispatched(AcceptInviteEvent::class);
    }


    /** @test */
    public function user_can_list_their_circles()
    {
        $this->actingAsUser();

        $circle = Circle::factory()->create();
        $circle->members()->create([
            'user_id' => $this->user->id,
            'position' => 1,
        ]);

        $this->getJson('/api/circles')
            ->assertOk()
            ->assertJsonFragment(['id' => $circle->id]);
    }

    /** @test */
    public function user_can_shuffle_positions_in_circle()
    {
        $this->actingAsUser();

        $circle = Circle::factory()->create([
            'select_position_method' => PositionSelectionMethodEnum::Random,
            'creator_id' => $this->user->id,
            'limit' => 3, // important: defines "full"
        ]);

        // Create members INCLUDING creator
        CircleMember::factory()
            ->count(3)
            ->sequence(
                ['user_id' => $this->user->id],
                [],
                []
            )
            ->create([
                'circle_id' => $circle->id,
            ]);

        // Sanity: circle must be full
        $this->assertTrue($circle->fresh()->isFull());

        $response = $this->postJson("/api/circles/{$circle->id}/shuffle");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Positions shuffled successfully',
            ]);

        // Assert all members now have unique positions
        $positions = CircleMember::where('circle_id', $circle->id)
            ->pluck('position')
            ->toArray();

        $this->assertCount(3, array_unique($positions));
        $this->assertEqualsCanonicalizing([1, 2, 3], $positions);
    }

    /** @test */
    public function creator_can_start_circle_cycle()
    {
        $this->actingAsUser();
        Event::fake();

        $circle = Circle::factory()->create([
            'creator_id' => $this->user->id,
            'limit' => 2,
            'start_date' => null,
            'status' => CircleStatusEnum::Pending,
        ]);

        // creator (verified)
        $creator = $this->user;
        $creator->update(['kyc_verified_at' => now()]);

        // second user (verified)
        $otherUser = User::factory()->create([
            'kyc_verified_at' => now(),
        ]);

        CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $creator->id,
        ]);

        CircleMember::factory()->create([
            'circle_id' => $circle->id,
            'user_id' => $otherUser->id,
        ]);

        $this->assertTrue($circle->fresh()->isFull());

        $response = $this->postJson("/api/circles/{$circle->id}/start");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Circle started']);

        $this->assertDatabaseHas('circles', [
            'id' => $circle->id,
            'status' => CircleStatusEnum::Active->value,
        ]);

        Event::assertDispatched(AuditLogged::class, fn ($event) =>
            $event->entityId === $circle->id &&
            $event->action === AuditAction::CIRCLE_STARTED->value
        );
    }


    /** @test */
    public function listener_creates_future_contributions_when_handled()
    {
        $circle = Circle::factory()->create([
            'start_date' => now(),
            'limit' => 3,
        ]);

        CircleMember::factory()
            ->count(3)
            ->create(['circle_id' => $circle->id]);

        app(CreateContributionsListener::class)
            ->handle(new CreateContributionsEvent($circle));

        $this->assertDatabaseCount(
            'circle_contributions',
            9
        );
    }
}
