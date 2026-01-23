<?php
namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Currency;
use Modules\Core\Models\UserMeta;
use Modules\Core\Repositories\Contracts\CurrencyRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;

class CurrencyRepository extends CoreRepository implements CurrencyRepositoryInterface
{
    protected Model $model;

    public function __construct(Currency $Currency)
    {
        $this->model = $Currency;
    }
}
