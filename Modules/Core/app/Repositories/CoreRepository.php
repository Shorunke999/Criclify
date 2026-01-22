<?php
namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

abstract class CoreRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function all(
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): Collection {
        return $this->withRelations($relations, $relationsCount)
            ->get($columns);
    }

    public function find(
        int $id,
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): ?Model {
        return $this->withRelations($relations, $relationsCount)
            ->select($columns)
            ->find($id);
    }

    public function findBy(
        string $field,
        mixed $value,
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): ?Model {
        return $this->withRelations($relations, $relationsCount)
            ->select($columns)
            ->where($field, $value)
            ->first();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);

        return $record
            ? $record->update($data)
            : false;
    }
    public function firstOrCreate(
        array $attributes,
        array $values = []
    ): Model {
        return $this->model->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(
        array $attributes,
        array $values = []
    ): Model {
        return $this->model->updateOrCreate($attributes, $values);
    }
    public function delete(int $id): bool
    {
        $record = $this->find($id);

        return $record
            ? $record->delete()
            : false;
    }

    protected function withRelations(
        ?array $relations = null,
        ?array $relationsCount = null
    ) {
        $query = $this->model->newQuery();

        if ($relations) {
            $query->with($relations);
        }

        if ($relationsCount) {
            $query->withCount($relationsCount);
        }

        return $query;
    }
}
