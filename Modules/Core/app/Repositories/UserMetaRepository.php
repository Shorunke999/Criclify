<?php
namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\UserMeta;
use Modules\Core\Repositories\Contracts\UserMetaRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;

class UserMetaRepository extends CoreRepository implements UserMetaRepositoryInterface
{
    protected Model $model;

    public function __construct(UserMeta $meta)
    {
        $this->model = $meta;
    }
    public function existsByReferralCode(string $code): bool
    {
        return $this->model->where('referral_code', $code)->exists();
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

}
