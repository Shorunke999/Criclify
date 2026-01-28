<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Payment\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository extends CoreRepository implements TransactionRepositoryInterface
{
    protected Model $model;

    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
    }
    public function generateTransactionReference(): string
    {
        do {
            $reference = 'TRX_CIR' . strtoupper(Str::random(10));
        } while (Transaction::where('reference', $reference )->exists());
        return $reference;
    }

    public function getUserTransactions(int $userId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query();
        $query->where('user_id', $userId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
