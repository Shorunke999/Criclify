<?php
namespace Modules\Waitlist\Services;

use Modules\Waitlist\Repositories\Contracts\WaitlistEntryRepositoryInterface;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\Notification;
use Modules\Referral\Services\ReferralService;
use Modules\Waitlist\Notifications\WaitlistConfirmation;
use PostHog\PostHog;

class WaitlistService
{
    use ResponseTrait;

    public function __construct(
        protected WaitlistEntryRepositoryInterface $entryRepo,
        protected ReferralService $referralService
    ) {}

    public function join(array $data)
    {
        try {
            if($this->entryRepo->findBy('email',$data['email'])) return $this->error_response('Waitlist Exists Already');

            $entry = $this->entryRepo->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'survey_data' => $data['survey'] ?? null,
                ]);
            //if waitlist was reffered.
            if(!empty($data['referral_code']))
            {
                 $this->referralService->logWaitlistReferral($entry,$data['referral_code']);
            }

            $newWaitlistCode = $this->referralService->waitlistCodeGenerate($entry);
            $referralLink = config('app.base_url')."/".$newWaitlistCode;

            // Email confirmation
            // Notification::route('mail', $entry->email)
            //     ->notify(new WaitlistConfirmation($entry));

            // Posthog
            // PostHog::capture([
            //     'distinctId' => $entry->email,
            //     'event' => 'WaitlistJoined',
            //     'properties' => [
            //         'has_referral' => (bool) $entry->referral_code,
            //     ],
            // ]);

            return $this->success_response([
                'referral_link' =>$referralLink
            ], 'Successfully joined the waitlist');
        } catch (Exception $e) {
            $this->reportError($e,"Waitlist",[
                    'action' => 'Join Waitlist',
                    'service' => 'WaitlistService'
            ]);
            return $this->error_response("Waitlist fails to create: ".$e->getMessage(), $e->getCode() ?: 400);
        }

    }

    public function export(array $filters)
    {
        try{
            return response()->streamDownload(function () use ($filters) {
                echo "Name,Email,Referral,Survey\n";
                $this->entryRepo
                ->cursorFiltered($filters)
                ->each(function ($entry) {

                    echo implode(',', [
                        $this->csv($entry->name),
                        $this->csv($entry->email),
                        $this->csv($entry->referral_code),
                        $entry->created_at,
                    ]) . "\n";
                });
            }, 'waitlist.csv');
        }catch(Exception $e)
        {
            $this->reportError($e,"Waitlist",[
                    'action' => 'Export Waitlist As Csv',
                    'service' => 'WaitlistService'
            ]);
            return $this->error_response("Waitlist fails to export as csv: ".$e->getMessage(), $e->getCode() ?: 400);
        }

    }

    private function csv(?string $value): string
    {
        return '"' . str_replace('"', '""', $value ?? '') . '"';
    }
}
