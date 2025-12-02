<?php

namespace App\Modules\Core\Repository\Interface;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Runner\DeprecationCollector\Collector;
use Ramsey\Collection\Collection;

interface CoreRepositoryInterface
{
    public function all(Model $model, array $columns = ['*'], ?array $relations = null, ?array $relationsCount = null): Collection;

    public function find(Model $model, int $id, array $columns=['*'], ?array $relations = null, ?array $relationsCount = null): Collection;

    public function findBy(Model $model, string $field, mixed $value, array $columns=['*'], ?array $relations = null, ?array $relationsCount = null): Collection;

    public function create(Model $model, array $data): Model;

    public function update(Model $model, int $id, array $data): bool;

    public function delete(Model $model, int $id): bool;
}
