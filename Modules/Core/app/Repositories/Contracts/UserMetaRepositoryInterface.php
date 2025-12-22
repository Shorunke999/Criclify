<?php
namespace Modules\Core\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface UserMetaRepositoryInterface extends BaseRepositoryInterface {
    public function existsByReferralCode(string $code): bool;
    public function leaderboard();
}
