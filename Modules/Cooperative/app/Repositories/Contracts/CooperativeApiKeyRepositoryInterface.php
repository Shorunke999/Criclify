<?php

namespace Modules\Cooperative\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface CooperativeApiKeyRepositoryInterface extends BaseRepositoryInterface
{
     public function findByHash(string $hashValue);
}
