<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Circle\Repositories\Contracts;

use Modules\Circle\Models\CircleContribution;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface ContributionRepositoryInterface extends BaseRepositoryInterface
{
     public function getPayableContribution(
        int $memberId
    ): ?CircleContribution;
    public function createFutureContributions(
        int $circleId,
        int $memberId,
        int $startCycle,
        int $cycles
    ): void;
    public function unpaidDueInDays(int $days): ?LazyCollection;
    public function overdueContributions(): ?LazyCollection;
    public function list(array $filters = []): LengthAwarePaginator;
}
