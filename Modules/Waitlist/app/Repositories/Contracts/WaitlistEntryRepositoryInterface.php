<?php
namespace Modules\Waitlist\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface WaitlistEntryRepositoryInterface extends BaseRepositoryInterface {
    public function findByEmail(string $email);
    public function existsByReferralCode(string $code): bool;
    public function cursorFiltered(array $filters);
}
