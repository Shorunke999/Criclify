<?php
namespace Modules\Auth\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface AuthRepositoryInterface extends BaseRepositoryInterface {
    public function findByEmail(string $email);
}
