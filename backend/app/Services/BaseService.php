<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Class BaseService
 *
 * Enhanced base service class providing common business logic patterns,
 * transaction management, event dispatching, audit logging, and cache invalidation.
 *
 * @template T of Model
 */
abstract class BaseService
{
    /**
     * The repository instance.
     *
     * @var \App\Repositories\BaseRepository
     */
    protected $repository;

    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Enable query logging.
     */
    protected bool $queryLogging = false;

    /**
     * Enable event dispatching.
     */
    protected bool $dispatchEvents = true;

    /**
     * Enable audit logging.
     */
    protected bool $auditLogging = true;

    /**
     * Enable cache invalidation.
     */
    protected bool $cacheInvalidation = true;

    /**
     * Cache tags for invalidation.
     */
    protected array $cacheTags = [];

    /**
     * The user performing the action (for audit logging).
     */
    protected $currentUser = null;

    /**
     * BaseService constructor.
     */
    public function __construct()
    {
        $this->initializeRepository();
        $this->initializeModelClass();
        $this->initializeCacheTags();
    }

    /**
     * Initialize the repository.
     */
    abstract protected function initializeRepository(): void;

    /**
     * Initialize the model class name.
     */
    protected function initializeModelClass(): void
    {
        $this->modelClass = get_class($this->repository->getModel());
    }

    /**
     * Initialize cache tags.
     */
    protected function initializeCacheTags(): void
    {
        $modelName = class_basename($this->modelClass);
        $this->cacheTags = [
            'global',
            strtolower($modelName) . 's',
            strtolower($modelName) . ':' . $this->modelClass,
        ];
    }

    /**
     * Set the current user for audit logging.
     *
     * @param mixed $user
     * @return self
     */
    public function setCurrentUser(mixed $user): self
    {
        $this->currentUser = $user;
        return $this;
    }

    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * Find a record by ID or throw exception.
     *
     * @param int $id
     * @param array $columns
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->repository->findOrFail($id, $columns);
    }

    /**
     * Find a record by a specific field.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->repository->findBy($field, $value, $columns);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $this->logQuery('Creating record', ['data' => array_keys($data)]);

        return DB::transaction(function () use ($data) {
            $this->beforeCreate($data);
            
            $model = $this->repository->create($data);
            
            $this->afterCreate($model);
            $this->dispatchEvent('created', $model);
            $this->logAudit('create', $model);
            $this->invalidateCache($model);

            return $model;
        });
    }

    /**
     * Update a record.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        $this->logQuery('Updating record', ['id' => $id, 'data' => array_keys($data)]);

        return DB::transaction(function () use ($id, $data) {
            $model = $this->findOrFail($id);
            $oldAttributes = $model->getOriginal();
            
            $this->beforeUpdate($model, $data);
            
            $model = $this->repository->update($id, $data);
            
            $this->afterUpdate($model);
            $this->dispatchEvent('updated', $model, ['old_attributes' => $oldAttributes]);
            $this->logAudit('update', $model, ['old_attributes' => $oldAttributes, 'new_attributes' => $data]);
            $this->invalidateCache($model);

            return $model;
        });
    }

    /**
     * Update a record by finding it first.
     *
     * @param Model $model
     * @param array $data
     * @return Model
     */
    public function updateModel(Model $model, array $data): Model
    {
        return $this->update($model->getKey(), $data);
    }

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool|null
     * @throws \Exception
     */
    public function delete(int $id): ?bool
    {
        $this->logQuery('Deleting record', ['id' => $id]);

        return DB::transaction(function () use ($id) {
            $model = $this->findOrFail($id);
            
            $this->beforeDelete($model);
            
            $result = $this->repository->delete($id);
            
            $this->afterDelete($model);
            $this->dispatchEvent('deleted', $model);
            $this->logAudit('delete', $model);
            $this->invalidateCache($model);

            return $result;
        });
    }

    /**
     * Delete a model instance.
     *
     * @param Model $model
     * @return bool|null
     */
    public function deleteModel(Model $model): ?bool
    {
        return $this->delete($model->getKey());
    }

    /**
     * Soft delete a record.
     *
     * @param int $id
     * @return bool|null
     */
    public function softDelete(int $id): ?bool
    {
        $model = $this->findOrFail($id);
        
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return DB::transaction(function () use ($model) {
                $this->beforeDelete($model);
                
                $model->delete();
                
                $this->afterDelete($model);
                $this->dispatchEvent('soft_deleted', $model);
                $this->logAudit('soft_delete', $model);
                $this->invalidateCache($model);

                return true;
            });
        }

        return $this->delete($id);
    }

    /**
     * Restore a soft-deleted record.
     *
     * @param int $id
     * @return bool|null
     */
    public function restore(int $id): ?bool
    {
        $model = $this->findOrFail($id);
        
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            return DB::transaction(function () use ($model) {
                $model->restore();
                
                $this->dispatchEvent('restored', $model);
                $this->logAudit('restore', $model);
                $this->invalidateCache($model);

                return true;
            });
        }

        return false;
    }

    /**
     * Execute a callback within a database transaction.
     *
     * @param callable $callback
     * @param int $attempts Number of times to attempt the transaction
     * @return mixed
     */
    protected function transaction(callable $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Hook called before creating a record.
     *
     * @param array $data
     * @return void
     */
    protected function beforeCreate(array &$data): void
    {
        // Override in child class if needed
    }

    /**
     * Hook called after creating a record.
     *
     * @param Model $model
     * @return void
     */
    protected function afterCreate(Model $model): void
    {
        // Override in child class if needed
    }

    /**
     * Hook called before updating a record.
     *
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        // Override in child class if needed
    }

    /**
     * Hook called after updating a record.
     *
     * @param Model $model
     * @return void
     */
    protected function afterUpdate(Model $model): void
    {
        // Override in child class if needed
    }

    /**
     * Hook called before deleting a record.
     *
     * @param Model $model
     * @return void
     */
    protected function beforeDelete(Model $model): void
    {
        // Override in child class if needed
    }

    /**
     * Hook called after deleting a record.
     *
     * @param Model $model
     * @return void
     */
    protected function afterDelete(Model $model): void
    {
        // Override in child class if needed
    }

    /**
     * Dispatch an event for the model action.
     *
     * @param string $action The action performed (created, updated, deleted)
     * @param Model $model The model instance
     * @param array $payload Additional payload data
     * @return void
     */
    protected function dispatchEvent(string $action, Model $model, array $payload = []): void
    {
        if (!$this->dispatchEvents) {
            return;
        }

        $modelName = class_basename($model);
        $eventClass = "App\\Events\\{$modelName}{$action}";

        if (class_exists($eventClass)) {
            Event::dispatch(new $eventClass($model, $payload));
        } else {
            // Dispatch a generic event
            Event::dispatch("model.{$action}", [
                'model' => $model,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                ...$payload,
            ]);
        }
    }

    /**
     * Log an audit entry for the action.
     *
     * @param string $action The action performed
     * @param Model $model The model instance
     * @param array $context Additional context
     * @return void
     */
    protected function logAudit(string $action, Model $model, array $context = []): void
    {
        if (!$this->auditLogging) {
            return;
        }

        $userId = $this->currentUser?->id ?? auth()->id();
        $userType = $this->currentUser ? get_class($this->currentUser) : (auth()->check() ? get_class(auth()->user()) : 'system');

        Log::channel('audit')->info("Model {$action}", [
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'user_id' => $userId,
            'user_type' => $userType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Invalidate cache for the model.
     *
     * @param Model $model
     * @return void
     */
    protected function invalidateCache(Model $model): void
    {
        if (!$this->cacheInvalidation) {
            return;
        }

        try {
            Cache::tags($this->cacheTags)->flush();
            
            // Also invalidate specific model cache
            Cache::forget($this->getModelCacheKey($model->getKey()));
        } catch (\Exception $e) {
            Log::warning('Cache invalidation failed', [
                'error' => $e->getMessage(),
                'model' => get_class($model),
                'id' => $model->getKey(),
            ]);
        }
    }

    /**
     * Get cache key for a specific model ID.
     *
     * @param int $id
     * @return string
     */
    protected function getModelCacheKey(int $id): string
    {
        $modelName = strtolower(class_basename($this->modelClass));
        return "{$modelName}:{$id}";
    }

    /**
     * Get a model from cache or database.
     *
     * @param int $id
     * @param int $ttl Cache TTL in seconds
     * @return Model|null
     */
    public function findCached(int $id, int $ttl = 3600): ?Model
    {
        $cacheKey = $this->getModelCacheKey($id);

        return Cache::tags($this->cacheTags)->remember($cacheKey, $ttl, function () use ($id) {
            return $this->find($id);
        });
    }

    /**
     * Log a query.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logQuery(string $message, array $context = []): void
    {
        if ($this->queryLogging) {
            Log::debug($message, $context);
        }
    }

    /**
     * Handle an exception.
     *
     * @param \Throwable $e
     * @param string $message
     * @param array $context
     * @return void
     * @throws \Exception
     */
    protected function handleException(\Throwable $e, string $message = 'An error occurred', array $context = []): void
    {
        $logContext = array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], $context);

        Log::error($message, $logContext);

        throw new \Exception($message, $e->getCode(), $e);
    }

    /**
     * Validate data against rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(array $data, array $rules, array $messages = []): array
    {
        return validator($data, $rules, $messages)->validate();
    }

    /**
     * Get validated data.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array
     */
    protected function getValidatedData(array $data, array $rules, array $messages = []): array
    {
        return validator($data, $rules, $messages)->validated();
    }

    /**
     * Check if user has permission.
     *
     * @param mixed $user
     * @param string $permission
     * @param Model|null $model
     * @return bool
     */
    protected function can(mixed $user, string $permission, ?Model $model = null): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->can($permission, $model);
    }

    /**
     * Authorize user action or throw exception.
     *
     * @param mixed $user
     * @param string $permission
     * @param Model|null $model
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorize(mixed $user, string $permission, ?Model $model = null): void
    {
        if (!$this->can($user, $permission, $model)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                "User does not have permission to {$permission}"
            );
        }
    }

    /**
     * Format pagination response.
     *
     * @param LengthAwarePaginator $paginator
     * @param array $additional
     * @return array
     */
    protected function formatPagination(LengthAwarePaginator $paginator, array $additional = []): array
    {
        return array_merge([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], $additional);
    }

    /**
     * Bulk create records.
     *
     * @param array $records
     * @return Collection
     */
    public function bulkCreate(array $records): Collection
    {
        return DB::transaction(function () use ($records) {
            $models = collect();

            foreach ($records as $data) {
                $models->push($this->create($data));
            }

            return $models;
        });
    }

    /**
     * Bulk update records.
     *
     * @param array $records Array of ['id' => ..., 'data' => [...]]
     * @return Collection
     */
    public function bulkUpdate(array $records): Collection
    {
        return DB::transaction(function () use ($records) {
            $models = collect();

            foreach ($records as $record) {
                $models->push($this->update($record['id'], $record['data']));
            }

            return $models;
        });
    }

    /**
     * Bulk delete records by IDs.
     *
     * @param array $ids
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $count = 0;

            foreach ($ids as $id) {
                if ($this->delete($id)) {
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Find or fail with custom message.
     *
     * @param int $id
     * @param string $message
     * @param array $columns
     * @return Model
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOrFailWithMessage(
        int $id,
        string $message = 'Resource not found',
        array $columns = ['*']
    ): Model {
        $model = $this->find($id, $columns);

        if (!$model) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException($message);
        }

        return $model;
    }

    /**
     * Search/filter helper method.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSearchQuery(array $filters = [])
    {
        $query = $this->repository->query();

        // Apply filters
        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                // Handle range filters
                if (isset($value['from'])) {
                    $query->where($field, '>=', $value['from']);
                }
                if (isset($value['to'])) {
                    $query->where($field, '<=', $value['to']);
                }
                if (isset($value['in'])) {
                    $query->whereIn($field, $value['in']);
                }
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Enable query logging.
     *
     * @return self
     */
    public function enableQueryLogging(): self
    {
        $this->queryLogging = true;
        return $this;
    }

    /**
     * Disable query logging.
     *
     * @return self
     */
    public function disableQueryLogging(): self
    {
        $this->queryLogging = false;
        return $this;
    }

    /**
     * Enable event dispatching.
     *
     * @return self
     */
    public function enableEvents(): self
    {
        $this->dispatchEvents = true;
        return $this;
    }

    /**
     * Disable event dispatching.
     *
     * @return self
     */
    public function disableEvents(): self
    {
        $this->dispatchEvents = false;
        return $this;
    }

    /**
     * Enable audit logging.
     *
     * @return self
     */
    public function enableAuditLogging(): self
    {
        $this->auditLogging = true;
        return $this;
    }

    /**
     * Disable audit logging.
     *
     * @return self
     */
    public function disableAuditLogging(): self
    {
        $this->auditLogging = false;
        return $this;
    }

    /**
     * Enable cache invalidation.
     *
     * @return self
     */
    public function enableCacheInvalidation(): self
    {
        $this->cacheInvalidation = true;
        return $this;
    }

    /**
     * Disable cache invalidation.
     *
     * @return self
     */
    public function disableCacheInvalidation(): self
    {
        $this->cacheInvalidation = false;
        return $this;
    }

    /**
     * Get the repository instance.
     *
     * @return \App\Repositories\BaseRepository
     */
    public function getRepository(): \App\Repositories\BaseRepository
    {
        return $this->repository;
    }

    /**
     * Get the model class name.
     *
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
