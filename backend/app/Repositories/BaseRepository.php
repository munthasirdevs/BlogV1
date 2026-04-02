<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Class BaseRepository
 *
 * Base repository implementing common CRUD operations.
 * All model-specific repositories should extend this class.
 *
 * @template T of Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Cache prefix for repository caching.
     */
    protected string $cachePrefix = 'repository:';

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Whether to cache queries.
     */
    protected bool $cacheEnabled = false;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Specify Model class name.
     *
     * @return string
     */
    abstract protected function model(): string;

    /**
     * Make Model instance.
     *
     * @return Model
     * @throws \RuntimeException
     */
    protected function makeModel(): Model
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \RuntimeException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Reset the model instance.
     */
    public function resetModel(): void
    {
        $this->makeModel();
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($field, $value)->first($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->clearCache();
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        $this->clearCache();
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): ?bool
    {
        $model = $this->findOrFail($id);
        $this->clearCache();
        return $model->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->where($field, $value)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection
    {
        return $this->model->whereIn($field, $values)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function with(array $relations, array $columns = ['*']): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $where = [], array $columns = ['*']): ?Model
    {
        $query = $this->model->query();

        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first($columns);
    }

    /**
     * Get paginated results with caching.
     *
     * @param int $perPage
     * @param array $columns
     * @param string|null $cacheKey
     * @return LengthAwarePaginator
     */
    public function paginateCached(
        int $perPage = 15,
        array $columns = ['*'],
        ?string $cacheKey = null
    ): LengthAwarePaginator {
        if (!$this->cacheEnabled) {
            return $this->paginate($perPage, $columns);
        }

        $cacheKey = $cacheKey ?? $this->getCacheKey('paginate_' . $perPage);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($perPage, $columns) {
            return $this->paginate($perPage, $columns);
        });
    }

    /**
     * Find by field with caching.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @param string|null $cacheKey
     * @return Model|null
     */
    public function findByFieldCached(
        string $field,
        mixed $value,
        array $columns = ['*'],
        ?string $cacheKey = null
    ): ?Model {
        if (!$this->cacheEnabled) {
            return $this->findBy($field, $value, $columns);
        }

        $cacheKey = $cacheKey ?? $this->getCacheKey("{$field}_{$value}");

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($field, $value, $columns) {
            return $this->findBy($field, $value, $columns);
        });
    }

    /**
     * Clear cache for this repository.
     */
    public function clearCache(): void
    {
        if (!$this->cacheEnabled) {
            return;
        }

        Cache::tags([$this->getCacheTag()])->flush();
    }

    /**
     * Enable caching.
     *
     * @param int $ttl Cache TTL in seconds
     * @return self
     */
    public function enableCache(int $ttl = 3600): self
    {
        $this->cacheEnabled = true;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Disable caching.
     *
     * @return self
     */
    public function disableCache(): self
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * Get cache key.
     *
     * @param string $suffix
     * @return string
     */
    protected function getCacheKey(string $suffix = ''): string
    {
        return $this->cachePrefix . $this->getModelName() . ':' . $suffix;
    }

    /**
     * Get cache tag.
     *
     * @return string
     */
    protected function getCacheTag(): string
    {
        return $this->cachePrefix . $this->getModelName();
    }

    /**
     * Get model name.
     *
     * @return string
     */
    protected function getModelName(): string
    {
        return class_basename($this->model);
    }

    /**
     * Get the underlying model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Begin a query on the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->model->newQuery();
    }

    /**
     * Find or create a record.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $this->clearCache();
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Update or create a record.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $this->clearCache();
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Delete records by field.
     *
     * @param string $field
     * @param mixed $value
     * @return int Number of deleted records
     */
    public function deleteBy(string $field, mixed $value): int
    {
        $this->clearCache();
        return $this->model->where($field, $value)->delete();
    }

    /**
     * Get records with where clause.
     *
     * @param array $where
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $query = $this->model->query();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->where($field, $value[0], $value[1]);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get($columns);
    }

    /**
     * Get records with eager loading.
     *
     * @param array $relations
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function withQuery(array $relations, array $columns = ['*'])
    {
        return $this->model->with($relations)->select($columns);
    }

    /**
     * Chunk results.
     *
     * @param int $count
     * @param callable $callback
     * @return bool
     */
    public function chunk(int $count, callable $callback): bool
    {
        return $this->model->chunk($count, $callback);
    }

    /**
     * Get distinct values for a field.
     *
     * @param string $field
     * @return array
     */
    public function distinct(string $field): array
    {
        return $this->model->distinct()->pluck($field)->toArray();
    }

    /**
     * Check if any records exist matching criteria.
     *
     * @param array $where
     * @return bool
     */
    public function exists(array $where = []): bool
    {
        $query = $this->model->query();

        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }
}
