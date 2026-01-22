<?php
namespace Modules\Core\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    public function all(
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): Collection;

    public function find(
        int $id,
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): ?Model;

    public function findBy(
        string $field,
        mixed $value,
        array $columns = ['*'],
        ?array $relations = null,
        ?array $relationsCount = null
    ): ?Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): bool;

    public function firstOrCreate(
        array $attributes,
        array $values = []
    ): Model;
     public function updateOrCreate(
        array $attributes,
        array $values = []
    ): Model;

    public function delete(int $id): bool;
}
