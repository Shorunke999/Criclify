<?php
namespace Modules\Referral\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Modules\Referral\Models\Referral;
use Modules\Referral\Repositories\Contracts\ReferralRepositoryInterface;

class ReferralRepository extends CoreRepository implements ReferralRepositoryInterface
{
    public function __construct(Referral $model)
    {
        $this->model = $model;
    }

    public function findByCode(string $code): ?Referral
    {
        return $this->model->where('code', $code)->first();
    }
    public function leaderboard(int $limit = 20)
    {
        return $this->model
            ->with('user:id,name,email')
            ->orderByDesc('referral_count')
            ->limit($limit)
            ->get([
                'user_id',
                'referral_count'
            ]);
    }
    public function existsForReferred(int $userId): bool
    {
        return $this->model->where('referred_id', $userId)->exists();
    }

}
