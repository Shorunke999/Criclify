<?php

namespace Modules\Circle\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Traits\ResponseTrait;
use Modules\Circle\Repositories\Contracts\CircleRepositoryInterface;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Circle\Enums\CircleStatusEnum;
use Modules\Circle\Repositories\Contracts\CircleInviteRepositoryInterface;
use Modules\Circle\Enums\PositionSelectionMethodEnum;
use Modules\Circle\Enums\InviteStatusEnum;
use Modules\Circle\Events\CreateContributionsEvent;
use Modules\Circle\Events\SendCircleInvite;
use Modules\Core\Events\AuditLogged;

class CircleService
{
    use ResponseTrait;

    public function __construct(
        protected CircleRepositoryInterface $circleRepository,
        protected CircleInviteRepositoryInterface $inviteRepo,
    ) {}

    public function createCircle(array $data, int $creatorId)
    {
        try {
            DB::beginTransaction();
            $circle = $this->circleRepository->createCircle($data, $creatorId);
            $circle->wallet()->create();
            // Dispatch event
            event(new AuditLogged(
                action: AuditAction::CIRCLE_CREATED->value,
                userId: $creatorId,
                entityType: get_class($circle),
                entityId: $circle->id
            ));
            event(new \Modules\Circle\Events\CircleCreatedEvent($circle));

            DB::commit();

            return $this->success_response($circle, 'Circle created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            $this->reportError($e, "CircleService", [
                'action' => 'create',
                'creator_id' => $creatorId,
                'data' => $data
            ]);
            return $this->error_response('Failed to create circle: ' . $e->getMessage(),$e->getCode() ?: 400);
        }
    }
    public function joinCircle(int $circleId, int $userId,$protected = false)
    {
        try {

             $circle = $this->circleRepository->findBy('id', $circleId);

            if ($circle->isFull()) {
                return $this->error_response('Circle is already full', 422);
            }

            if (!$circle->multiple_position && $circle->members()->where('user_id', $userId)->exists()) {
                 return $this->error_response('User is already a member and multiple positions is not allowed for this circle', 422);
            }
            $no_of_times = $circle->members()->where('user_id', $userId)->count() ?? 0;
            $circleMember = $circle->members()->create([
                'user_id' => $userId,
                'position' =>$circle->positioning_method == 'sequence' ? $circle->getNextPosition() : 0,
                'no_of_times' => $no_of_times + 1
            ]);

            event(new \Modules\Circle\Events\MemberJoinedEvent($circleMember));

            if($protected) return $circleMember;

            return $this->success_response($circleMember, 'Successfully joined the circle');

        } catch (Exception $e) {
            if($protected){
                throw $e;
                return;
            }
            $this->reportError($e, "CircleService", [
                'action' => 'join circle',
                'circle_id' => $circleId,
                'user_id' => $userId
            ]);
            return $this->error_response($e->getMessage());
        }
    }

    public function inviteToCircle(int $circleId, array $contacts, int $inviterId)
    {
        try {
            DB::beginTransaction();

            $circle = $this->circleRepository->find($circleId);

            if (!$circle->members()->where('user_id', $inviterId)->exists()) {
                return $this->error_response('You must be a member to invite others', 403);
            }

            $invites = [];
            foreach ($contacts as $contact) {
                $existingUser = User::where('email', $contact)->first() ?: null;

                // Create invite
                $invites[] = $circle->invites()->updateOrCreate([
                    'contact' => $contact,
                ],[
                    'inviter_id' => $inviterId,
                    'invitee_id' => $existingUser?->id,
                    'token' => Str::random(32),
                    'expires_at' => now()->addDays(7),
                    'status' => InviteStatusEnum::Pending
                ]);

            }

            DB::commit();

            foreach ($invites as $invite) {
                event(new SendCircleInvite($circle, $invite, $invite->invitee));
            }


            return $this->success_response($invites, 'Invites sent successfully');

        } catch (Exception $e) {
            DB::rollBack();
            $this->reportError($e, "CircleService", [
                'action' => 'invite_to_circle',
                'circle_id' => $circleId,
            ]);
            return $this->error_response('Failed to send invites: ' . $e->getMessage());
        }
    }
    public function acceptInvite(string $token,int $userId)
    {
        try {
            DB::beginTransaction();

            // Find invite by token or ID
            $invite = $this->inviteRepo->findBy('token', $token);

            if (!$invite) {
                return $this->error_response('Invalid invite', 404);
            }

            if ($invite->status !==  InviteStatusEnum::Pending) {
                return $this->error_response('Invite already ' . $invite->status, 422);
            }

            if ($invite->expires_at && $invite->expires_at->isPast()) {
                $invite->update(['status' => 'expired']);
                return $this->error_response('Invite has expired', 422);
            }

            // VERIFY: Ensure this invite belongs to current user
            if ($invite->invitee_id && $invite->invitee_id !== $userId) {
                return $this->error_response('This invite is for a different user', 403);
            }

            // Update invite status
            $invite->update([
                'status' => InviteStatusEnum::Accepted,
                'accepted_at' => now(),
                'invitee_id' => $userId,
            ]);
            $circleMember = $this->joinCircle($invite->circle_id, $userId,true);

            DB::commit();
            event(new \Modules\Circle\Events\AcceptInviteEvent($invite,$circleMember));

            return $this->success_response($circleMember, 'Successfully joined circle');

        } catch (Exception $e) {
            DB::rollBack();
            Log::info('invites accepted',[
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 400
            ]);
            $this->reportError($e, "CircleService", [
                'action' => 'accept_invite',
                'token' => $token,
            ]);
            return $this->error_response('Failed to accept invite: ' . $e->getMessage());
        }
    }
    public function shufflePosition(int $circleId,int $userId)
    {
        try{
            $circle = $this->circleRepository->findBy('id',$circleId);

            if (!$circle)  return $this->error_response('Circle not found', 404);

            if($circle->creator_id != $userId) return $this->error_response('Only the creator can shuffle positions',403);

            if(!$circle->isFull()) return $this->error_response('Circle memeber is not complete. Pls amke sure they do.',422);

            if($circle->select_position_method !== PositionSelectionMethodEnum::Random) return $this->error_response("Position selection method must be 'random'",422);

            DB::beginTransaction();

            $members = $circle->members()->get()->shuffle()->values();

             foreach ($members as $index => $member) {
                $member->update([
                    'position' => $index + 1
                ]);
            }

            DB::commit();

            return $this->success_response(
                $circle->members()->orderBy('position')->get(),
                'Positions shuffled successfully'
            );
        }catch(Exception $e)
        {
              $this->reportError($e, "CircleService", [
                'action' => 'shuffle Position',
                'circle_id' => $circleId,
                'user_id' => $userId
            ]);
            return $this->error_response($e->getMessage());
        }
    }

    public function startCycle(int $circleId,int $userId)
    {
         try{
            $circle = $this->circleRepository->findBy('id',$circleId);

            if (!$circle)  return $this->error_response('Circle not found', 404);

            if($circle->creator_id != $userId) return $this->error_response('Only the creator can start cycle',403);

            if(!$circle->isFull()) return $this->error_response('Circle memeber is not complete. Pls amke sure they do.',422);

            if($circle->hasUnverifiedMembers()) return $this->error_response("Some of the members kyc is not completed",403);

            if($circle->start_date !== null) return $this->error_response( 'Circle cycle already started', 422);

            $circle->update([
                'start_date' =>now(),
                'end_date' => $circle->endDate(),
                'status' => CircleStatusEnum::Active
            ]);
            event(new AuditLogged(
                action: AuditAction::CIRCLE_STARTED->value,
                userId: $userId,
                entityType: get_class($circle),
                entityId: $circle->id
            ));
            return $this->success_response(
                [],
                'Circle started'
            );
        }catch(Exception $e)
        {
              $this->reportError($e, "CircleService", [
                'action' => 'start Circle',
                'circle_id' => $circleId
            ]);
            return $this->error_response($e->getMessage());
        }
    }
    public function getCircleDetails(int $circleId, int $userId)
    {
        try {
            $circle = $this->circleRepository->findActiveCircleByUser($circleId, $userId);

            if (!$circle) {
                return $this->error_response('Circle not found or you are not a member', 404);
            }

            $circleDetails = $this->circleRepository->getCircleWithMembers($circleId);

            return $this->success_response($circleDetails, 'Circle details retrieved');

        } catch (Exception $e) {
            $this->reportError($e, "CircleService", [
                'action' => 'get_details',
                'circle_id' => $circleId,
                'user_id' => $userId
            ]);
            return $this->error_response('Failed to get circle details');
        }
    }
    public function getUserCircles(array $filters = [],int $userId)
    {
        try {
            $circles = $this->circleRepository->getUserCircles($userId, $filters);

            return $this->success_response($circles, 'User circles retrieved');

        } catch (Exception $e) {
            $this->reportError($e, "CircleService", [
                'action' => 'list_user_circles',
                'user_id' => $userId,
                'filters' => $filters
            ]);
            return $this->error_response('Failed to retrieve circles');
        }
    }
}
