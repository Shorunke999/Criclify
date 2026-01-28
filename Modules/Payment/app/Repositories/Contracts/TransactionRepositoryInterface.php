<?php


namespace Modules\Payment\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface TransactionRepositoryInterface extends BaseRepositoryInterface
{
    public function generateTransactionReference(): string;
    public function getUserTransactions(int $userId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator;
}
