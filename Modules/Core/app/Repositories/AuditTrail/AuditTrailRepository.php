<?php

namespace Modules\Core\Repositories\AuditTrail;

use Modules\Core\Repositories\CoreRepository;
use Modules\Core\Models\AuditTrail;

class AuditTrailRepository extends CoreRepository
{
    public function __construct(AuditTrail $model)
    {
        $this->model = $model;
    }
}
