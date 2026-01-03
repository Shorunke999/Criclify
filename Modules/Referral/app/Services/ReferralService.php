<?php
namespace Modules\Referral\Services;

use App\Enums\ReferralStatus;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Str;
use Modules\Circle\Models\Circle;
use Modules\Circle\Repositories\Contracts\CircleRepositoryInterface;
use Modules\Circle\Services\CircleService;
use Modules\Core\Models\UserMeta;
use Modules\Core\Repositories\Contracts\UserMetaRepositoryInterface;
use Modules\Referral\Events\ReferralMilestoneReached;
use Modules\Referral\Repositories\Contracts\ReferralRepositoryInterface;
use Modules\Waitlist\Models\WaitlistEntry;
use Modules\Waitlist\Repositories\Contracts\WaitlistEntryRepositoryInterface;

class ReferralService
{
    use ResponseTrait;

    public function __construct(
        protected ReferralRepositoryInterface $repo,
        protected UserMetaRepositoryInterface $metaRepo,
        protected WaitlistEntryRepositoryInterface $waitlistEntryRepo,
        protected CircleRepositoryInterface $circleRepo,
        protected CircleService $circleService

    ) {}

    public function generate()
    {
        try{
            $user = auth()->user();

            $meta = $user->meta()->firstOrCreate([]);

            do {
                $code = 'CRIC_' . strtoupper(Str::random(8));
            } while ($this->metaRepo->existsByReferralCode($code));

            $meta->update([
                'referral_code' => $code,
            ]);

            return $this->success_response([
                'code' => $code
            ], 'Referral code generated');
        }catch(Exception $e)
        {
            $this->reportError($e,"Referral",[
                 'action' => 'generate',
                 'service' => 'referralervice'
            ]);
            return $this->error_response('Error when genrating referral code: '.$e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function waitlistCodeGenerate(WaitlistEntry $newEntry)
    {
        try{
            do {
                $code = 'WL_' . strtoupper(Str::random(8));
            } while ($this->waitlistEntryRepo->existsByReferralCode($code));

            $newEntry->update([
                'referral_code' => $code,
            ]);
            return $code;

        }catch(Exception $e)
        {
            throw $e;
        }
     }

    public function logReferralByCode(string $referralCode, int $referredEntityId, string $referredEntityType = 'user')
    {
        try {
            // Determine referral type from code prefix
            $referralType = $this->determineReferralType($referralCode);

            if (!$referralType) {
                return null; // Invalid code format
            }

            // Route to appropriate handler
            return match($referralType) {
                'user' => $this->processUserReferral($referralCode, $referredEntityId),
                'waitlist' => $this->processWaitlistReferral($referralCode, $referredEntityId),
                default => null
            };

        } catch (Exception $e) {
            $this->reportError($e, 'Referral', [
                'action' => 'logReferralByCode',
                'referral_code' => $referralCode,
                'referred_entity_id' => $referredEntityId,
                'referred_entity_type' => $referredEntityType,
            ]);
            throw $e;
        }
    }

    /**
     * Determine referral type from code prefix
     */
    private function determineReferralType(string $code): ?string
    {
        return match(true) {
            str_starts_with($code, 'CRIC_') => 'user',
            str_starts_with($code, 'WL_') => 'waitlist',
            default => null
        };
    }

    /**
     * Process user referral
     */
    private function processUserReferral(string $referralCode, int $referredUserId): ?array
    {
        $referrerMeta = $this->metaRepo->findBy('referral_code', $referralCode);

        if (!$referrerMeta) {
            return null;
        }

        // Prevent self-referral
        if ($referrerMeta->user_id === $referredUserId) {
            return null;
        }

         $this->repo->create([
            'referrer_id' => $referrerMeta->user_id,
            'referral_type' => 'user',
            'referred_id' => $referredUserId,
        ]);

        $referrerMeta->increment('referral_count');

        event(new ReferralMilestoneReached(
            $referrerMeta->user_id,
            $referrerMeta->referral_count
        ));

        return [
            'referral_entity' => 'user',
            'entity_id' => $referrerMeta->user_id,
            'referral_count' => $referrerMeta->referral_count,
        ];
    }

    /**
     * Process waitlist referral
     */
    private function processWaitlistReferral(string $referralCode, int $referredEntryId): ?array
    {
        $referrerEntry = $this->waitlistEntryRepo->findBy('referral_code', $referralCode);

        if (!$referrerEntry) {
            return null;
        }
        // Prevent self-referral
        if ($referrerEntry->id === $referredEntryId) {
            return null;
        }

         $this->repo->create([
            'referrer_id' => $referrerEntry->id,
            'referrer_type' => 'waitlist',
            'referred_id' => $referredEntryId,
        ]);

        $referrerEntry->increment('referral_count');

        event(new ReferralMilestoneReached(
            $referrerEntry->id,
            $referrerEntry->referral_count,
            'waitlist'
        ));

        return [
            'referral_entity' => 'waitlist',
            'entity_id' => $referrerEntry->id,
            'referral_count' => $referrerEntry->referral_count,
        ];
    }

    public function leaderboard(int $limit = 20)
    {
        $leaders = $this->metaRepo->leaderboard($limit);

        return $this->success_response(
            $leaders,
            'Referral leaderboard'
        );
    }
}
