<?php

namespace App\Repositories;

use App\Contracts\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model) {}

    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->newQuery()->latest()->get($columns);
    }

    /**
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->newQuery()->latest()->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->newQuery()->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->newQuery()->findOrFail($id, $columns);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    protected function newQuery()
    {
        return $this->model->newQuery();
    }
}
