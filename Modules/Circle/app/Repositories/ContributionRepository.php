<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Circle\Repositories;

use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Models\CircleContribution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Modules\Circle\Models\Circle;

class ContributionRepository extends CoreRepository implements ContributionRepositoryInterface
{
    protected Model $model;

    public function __construct(CircleContribution $contribution)
    {
        $this->model = $contribution;
    }

    public function createFutureContributions(
        int $circleId,
        int $memberId,
        int $startCycle,
        int $cycles
    ): void {

        $circle = Circle::findOrFail($circleId);
        $rows = [];
        for ($i = 0; $i < $cycles; $i++) {
            $cycleIndex = $startCycle + $i;
            if (
                $this->model->where('circle_id', $circleId)
                    ->where('circle_member_id', $memberId)
                    ->where('cycle_index', $cycleIndex)
                    ->exists()
            ) {
                continue;
            }

            $rows[] = [
                'circle_id' => $circleId,
                'circle_member_id' => $memberId,
                'cycle_index' => $cycleIndex,
                'due_date' => $circle
                    ->cycleDateByIndex($cycleIndex)
                    ->addDays(config('circle.contribution.grace_days', 3)),
                'amount' => $circle->amount,
                'paid_amount' => 0,
                'status' => StatusEnum::Pending,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            $this->model->insert($rows);
        }
    }

    public function getPayableContribution(
        int $memberId
    ): ?CircleContribution {
        return $this->model->where('circle_member_id', $memberId)
            ->whereIn('status', [
                StatusEnum::Pending,
                StatusEnum::Partpayment,
            ])
            ->orderBy('cycle_index')
            ->first();
    }

    public function unpaidDueInDays(int $days): ?LazyCollection
    {
        return CircleContribution::query()
            ->whereIn('status', [
                StatusEnum::Pending,
                StatusEnum::Partpayment,
            ])
            ->whereDate('due_date', now()->addDays($days))
            ->cursor();
    }

    public function overdueContributions(): ?LazyCollection
    {
       return CircleContribution::whereIn('status', [
            StatusEnum::Pending,
            StatusEnum::Partpayment,
        ])
        ->whereDate('due_date', '<', now())
        ->cursor();
    }


    public function list(array $filters = []): LengthAwarePaginator
    {
        return CircleContribution::query()
            ->with(['circle', 'member.user'])
            ->when($filters['circle_id'] ?? null, fn (Builder $q, $v) =>
                $q->where('circle_id', $v)
            )
            ->when($filters['member_id'] ?? null, fn (Builder $q, $v) =>
                $q->where('circle_member_id', $v)
            )
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) =>
                $q->whereHas('member', fn ($m) =>
                    $m->where('user_id', $v)
                )
            )
            ->when($filters['status'] ?? null, fn (Builder $q, $v) =>
                $q->where('status', $v)
            )
            ->when($filters['due_from'] ?? null, fn (Builder $q, $v) =>
                $q->whereDate('due_date', '>=', $v)
            )
            ->when($filters['due_to'] ?? null, fn (Builder $q, $v) =>
                $q->whereDate('due_date', '<=', $v)
            )
            ->latest('due_date')
            ->paginate($filters['per_page'] ?? 15);
    }


}
