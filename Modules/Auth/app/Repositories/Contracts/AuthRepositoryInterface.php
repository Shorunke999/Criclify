<?php
namespace Modules\Auth\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface AuthRepositoryInterface extends BaseRepositoryInterface {
    public function findByEmail(string $email);
    public function getPendingRoles();
}
