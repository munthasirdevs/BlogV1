<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface RepositoryInterface
 *
 * Contract for all repository classes.
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or throw exception.
     *
     * @param int $id
     * @param array $columns
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find a record by a specific field.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id): ?bool;

    /**
     * Get records by specific field.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Collection
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Get records where field in values.
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     * @return Collection
     */
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection;

    /**
     * Get records with relationships.
     *
     * @param array $relations
     * @param array $columns
     * @return Collection
     */
    public function with(array $relations, array $columns = ['*']): Collection;

    /**
     * Count records.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get first record matching criteria.
     *
     * @param array $where
     * @param array $columns
     * @return Model|null
     */
    public function first(array $where = [], array $columns = ['*']): ?Model;
}
