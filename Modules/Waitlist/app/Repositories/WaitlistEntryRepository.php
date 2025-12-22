<?php
namespace Modules\Waitlist\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Modules\Waitlist\Models\WaitlistEntry;
use Modules\Waitlist\Repositories\Contracts\WaitlistEntryRepositoryInterface;

class WaitlistEntryRepository extends CoreRepository implements WaitlistEntryRepositoryInterface
{
    public function __construct(WaitlistEntry $model)
    {
        $this->model = $model;
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }
    public function existsByReferralCode(string $code): bool
    {
        return $this->model->where('referral_code', $code)->exists();
    }

    public function cursorFiltered(array $filters)
    {
        $query = $this->model->newQuery();

        // Referral code
        if (!empty($filters['referral_code'])) {
            $query->where('referral_code', $filters['referral_code']);
        }

        // Has referral
        if (array_key_exists('has_referral', $filters)) {
            $filters['has_referral']
                ? $query->whereNotNull('referral_code')
                : $query->whereNull('referral_code');
        }

        // Date range
        if (!empty($filters['joined_from'])) {
            $query->whereDate('created_at', '>=', $filters['joined_from']);
        }

        if (!empty($filters['joined_to'])) {
            $query->whereDate('created_at', '<=', $filters['joined_to']);
        }

        return $query->orderBy('created_at')->cursor();
    }

}
