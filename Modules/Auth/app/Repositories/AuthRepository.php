<?php
namespace Modules\Auth\Repositories;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;

class AuthRepository extends CoreRepository implements AuthRepositoryInterface
{
    protected Model $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }
    public function getPendingCreators()
    {
        return $this->model->role('creator')
            ->where('account_status', AccountStatus::PENDING)
            ->latest()
            ->get();
    }
}
