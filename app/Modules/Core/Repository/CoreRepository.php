<?php

namespace App\Modules\Core\Repository;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Collection\Collection;

class CoreRepository
{
   public function all(Model $model,array $column = ['*'], ?array $relations = null, ?array $relationsCount = null): Collection
   {
        $query = $this->withRelations($model,$relations,$relationsCount);
        return new Collection($query->get($column));

   }
    public function find(Model $model, int $id, array $columns=['*'], ?array $relations = null, ?array $relationsCount = null): Collection
    {
        $query = $this->withRelations($model,$relations,$relationsCount);
        return new Collection($query->findOrFail($id,$columns));
    }

    public function findBy(Model $model , string $field, mixed $value, array $columns=['*'],?array $relations = null, ?array $relationsCount = null): Collection
    {
         $query = $this->withRelations($model,$relations,$relationsCount);
         return new Collection($query->where($field,$value)->get($columns));
    }
    public function create(Model $model, array $data): Model
    {
        $record = $model::create($data);
        return $record;
    }

     public function update(Model $model, int $id, array $data): bool
     {
         $record = $model::updateOrCreate(
            ['id' => $id],
            $data);
        return $record ? true : false;
     }

     public function delete(Model $model, int $id): bool
     {
        $record = $model::find($id);
        return $record->destroy();

     }

   private function withRelations(Model $model, ?array $relations = null,?array $relationsCount = null)
   {
        $query = $model->newQuery();
        if($relations)
        {
        $query->with($relations);
        }
        if($relationsCount)
        {
            $query->withCounts($relationsCount);
        }
        return $query;
   }
}
