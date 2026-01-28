<?php
namespace Modules\Circle\Services;

use App\Traits\ResponseTrait;
use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Events\ContributionOverdueEvent;
use Modules\Circle\Events\ContributionReminderEvent;
use Modules\Circle\Models\CircleMember;
use Modules\Circle\Repositories\Contracts\CircleRepositoryInterface;
use Modules\Payment\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;
use Modules\Payment\Services\TransactionService;

class ContributionService
{
    use ResponseTrait;

    public function __construct(
        protected CircleRepositoryInterface $circleRepo,
        protected ContributionRepositoryInterface $contributionRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected TransactionService $transactionService
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
            DB::beginTransaction();
            $user = auth()->user();

            $member = CircleMember::where('id', $memberId)
                ->firstOrFail();

            // Resolve contributions to be paid
            $contributions = $contributionId
                ? collect([$this->resolveSingleContribution($contributionId, $memberId)])
                : $this->resolvePayableContributionsForPayment($member, (float) $data['amount']);

            if ($contributions->isEmpty()) {
                return $this->error_response('No payable contribution found', 422);
            }
            $user->debitWallet($data['amount']);

            $transaction = $this->transactionService->contributeToCircle($user,$member->circle, $data['amount']);
            // Process contribution payments
            $this->processContributionPayment($transaction, $contributions);
            // Credit circle wallet
            $member->circle->creditWallet($data['amount']);

            DB::commit();
            return $this->success_response([], 'COntribution Payment Successfull');

        } catch (Exception $e) {
            DB::rollBack();
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

    private function processContributionPayment($transaction, Collection $contributions)
    {
        $remaining = $transaction->amount;
        foreach ($contributions as $contribution) {

            if ($remaining <= 0) break;

            $due = $contribution->amount - $contribution->paid_amount;
            $allocated = min($remaining, $due);
            $remaining -= $allocated;

            // Attach contribution to transaction via transactables pivot
            $transaction->contributions()->attach($contribution->id, [
                'amount' => $allocated
            ]);

            // Update contribution
            $newPaidAmount = $contribution->paid_amount + $allocated;
            $isPaid = $newPaidAmount >= $contribution->amount;

            $contribution->update([
                'paid_amount' => $newPaidAmount,
                'status' => $isPaid ? StatusEnum::Paid : StatusEnum::Partpayment,
                'paid_at' => $isPaid ? now() : $contribution->paid_at,
            ]);

        }

        event(new \Modules\Core\Events\AuditLogged(
            action: \App\Enums\AuditAction::CONTRIBUTION_PAID->value,
            entityType: get_class($transaction),
            entityId: $transaction->id,
            userId: $transaction->user_id,
            metadata: [
                'amount' => $transaction->amount,
                'from_wallet_id' => auth()->user()->wallet->id,
                'to_entity_type' => 'circle',
                'to_entity_id' => $transaction->circle_id,
                'contribution_ids' => $contributions->pluck('id')->values()->all()
            ]
        ));
    }
}
