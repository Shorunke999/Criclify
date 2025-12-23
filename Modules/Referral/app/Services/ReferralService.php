<?php
namespace Modules\Referral\Services;

use App\Enums\ReferralStatus;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Str;
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
        protected WaitlistEntryRepositoryInterface $waitlistEntryRepo

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

    public function logReferral(UserMeta $meta,string $referralCode)
    {
        try{
             $referrerMeta = $this->metaRepo->findBy('referral_code', $referralCode);

            if (!$referrerMeta) {
                return;
            }

            // Prevent self-referral
            if ($referrerMeta->user_id === $meta->user_id) {
                return;
            }

            // Prevent double referral
            if ($this->repo->existsForReferred($meta->user_id)) {
                return;
            }

            $this->repo->create([
                'referrer_id' => $referrerMeta->user_id,
                'referred_id' => $meta->user_id,
                'referral_type' => 'user'
            ]);

            $referrerMeta->increment('referral_count');

            event(new ReferralMilestoneReached(
                $referrerMeta->user_id,
                $referrerMeta->referral_count
            ));
        }catch(Exception $e)
        {
            throw $e;
        }


    }

    public function logWaitlistReferral(WaitlistEntry $entry,string $referralCode)
    {
        try{
             $referrerEntry = $this->waitlistEntryRepo->findBy('referral_code', $referralCode);

            if (!$referrerEntry) {
                return;
            }

            // Prevent self-referral
            if ($entry->id === $referrerEntry->id) {
                return;
            }

            $this->repo->create([
                'referrer_id' => $referrerEntry->id,
                'referred_id' => $entry->id,
                'referral_type' => 'waitlist'
            ]);

            $referrerEntry->increment('referral_count');

            // event(new ReferralMilestoneReached(
            //     $referrerEntry->id,
            //     $referrerEntry->referral_count
            // ));
        }catch(Exception $e)
        {
            throw $e;
        }


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
