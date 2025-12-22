<?php
namespace Modules\Referral\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Referral\Models\Referral;

interface ReferralRepositoryInterface extends BaseRepositoryInterface {
    public function findByCode(string $code): ?Referral;
    public function leaderboard();
     public function existsForReferred(int $userId): bool;
}
