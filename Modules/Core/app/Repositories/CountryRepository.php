<?php
namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Country;
use Modules\Core\Models\UserMeta;
use Modules\Core\Repositories\Contracts\CountryRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;

class CountryRepository extends CoreRepository implements CountryRepositoryInterface
{
    protected Model $model;

    public function __construct(Country $country)
    {
        $this->model = $country;
    }
}
