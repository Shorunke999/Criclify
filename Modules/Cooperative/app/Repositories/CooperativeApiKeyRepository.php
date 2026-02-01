<?php

namespace Modules\Cooperative\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Cooperative\Models\CooperativeApiKey;
use Modules\Cooperative\Repositories\Contracts\CooperativeRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
class CooperativeApiKeyRepository extends CoreRepository implements CooperativeRepositoryInterface
{
    protected Model $model;

    public function __construct(CooperativeApiKey $cooperativeApiKey)
    {
        $this->model = $cooperativeApiKey;
    }
    public function findByHash(string $hashValue)
    {
        return $this->model->findBy('key_hash', $hashValue);
    }
}
