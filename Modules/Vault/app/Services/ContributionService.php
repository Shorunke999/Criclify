<?php
namespace Modules\Circle\Services;

use App\Traits\ResponseTrait;
use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;
use Exception;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Events\ContributionOverdueEvent;
use Modules\Circle\Events\ContributionReminderEvent;
use Modules\Circle\Models\CircleMember;
use Modules\Circle\Repositories\Contracts\CircleRepositoryInterface;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Services\PaymentService;

class ContributionService
{
    use ResponseTrait;

    public function __construct(
        protected CircleRepositoryInterface $circleRepo,
        protected ContributionRepositoryInterface $contributionRepo,
        protected PaymentService $paymentService
    ) {}

    public function contributionReminder():void{
        foreach (config('circle.contribution.reminder_days', [3, 1]) as $day) {
            $this->contributionRepo->unpaidDueInDays($day)->each(function ($contribution) use ($day) {
                event(new ContributionReminderEvent($contribution, $day));
            });
        }
    }

    public function handleOverdue():void
    {
        $this->contributionRepo->overdueContributions()
        ->each(function ($c) {
             $c->update([
                'status' => StatusEnum::Overdue
             ]);
             event(new ContributionOverdueEvent($c));
        });
    }

    public function listContributions(array $filters = [])
    {
        try {
            $contributions = $this->contributionRepo->list($filters);

            return $this->success_response(
                $contributions,
                'Contributions retrieved',
                200
            );
        } catch (Exception $e) {
            $this->reportError($e, 'ContributionService', [
                'action' => 'list_contributions',
                'filters' => $filters,
            ]);

            return $this->error_response(
                'Failed to retrieve contributions'
            );
        }
    }

    public function payForContribution(
        int $memberId,
        array $data,
    ?int $contributionId = null
    ) {
        try {
            $userId = auth()->id();

            $member = CircleMember::where('id', $memberId)
                ->firstOrFail();

            // Resolve contributions to be paid
            $contributions = $contributionId
                ? collect([$this->resolveSingleContribution($contributionId, $memberId)])
                : $this->resolvePayableContributionsForPayment($member, (float) $data['amount']);

            if ($contributions->isEmpty()) {
                return $this->error_response('No payable contribution found', 422);
            }

            $transactionData = [
                'user_id' => $userId,
                'circle_id' => $member->circle_id,
                'type' => TransactionTypeEnum::Contribution,
                'amount' => $data['amount'],
                'type_ids' => $contributions->pluck('id')->all()
            ];

            $payment = $this->paymentService
                ->initiatePayment($transactionData);

            return $this->success_response($payment, 'Payment initiated');

        } catch (Exception $e) {
            $this->reportError($e, 'ContributionService', [
                'action' => 'pay_for_contribution',
            ]);

            return $this->error_response('Failed to initiate payment');
        }
    }

    private function resolvePayableContributionsForPayment(
        CircleMember $member,
        float $amount
    ) {
        $circle = $member->circle;
        $unitAmount = (float) ($circle->amount / $circle->limit);

        // How many cycles does this amount cover?
        $cyclesToCover = (int) ceil($amount / $unitAmount);

        //Get existing unpaid contributions
        $existing = collect($this->contributionRepo->getPayableContribution($member->id))
            ->take($cyclesToCover);

        return $existing;
    }

    private function resolveSingleContribution(
    int $contributionId
    ){

        $contribution = $this->contributionRepo->findBy('id', $contributionId);

        if (! in_array($contribution->status, [
            StatusEnum::Pending,
            StatusEnum::Partpayment
        ])) {
            throw new \Exception('Contribution not payable');
        }

        return $contribution;
    }
}
