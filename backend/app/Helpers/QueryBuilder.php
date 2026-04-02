<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class QueryBuilder
 *
 * Helper class for building filterable, sortable, and searchable API queries.
 * Provides a fluent interface for common query operations.
 *
 * @package App\Helpers
 */
class QueryBuilder
{
    /**
     * The query builder instance.
     */
    protected Builder $query;

    /**
     * Allowed filter fields.
     */
    protected array $allowedFilters = [];

    /**
     * Allowed sort fields.
     */
    protected array $allowedSorts = ['created_at'];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'created_at';

    /**
     * Default sort direction.
     */
    protected string $defaultDirection = 'desc';

    /**
     * Allowed includes (relationships).
     */
    protected array $allowedIncludes = [];

    /**
     * Allowed fields for sparse fieldsets.
     */
    protected array $allowedFields = ['*'];

    /**
     * Searchable fields.
     */
    protected array $searchFields = [];

    /**
     * Search operator (OR/AND).
     */
    protected string $searchOperator = 'OR';

    /**
     * Filters to apply.
     */
    protected array $filters = [];

    /**
     * Sorts to apply.
     */
    protected array $sorts = [];

    /**
     * Includes to apply.
     */
    protected array $includes = [];

    /**
     * Fields to select.
     */
    protected array $fields = [];

    /**
     * Search term.
     */
    protected ?string $search = null;

    /**
     * QueryBuilder constructor.
     *
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Create a new QueryBuilder instance.
     *
     * @param Model|string $model
     * @return static
     */
    public static function for(Model|string $model): static
    {
        $query = is_string($model) ? $model::query() : $model->newQuery();
        return new static($query);
    }

    /**
     * Set allowed filters.
     *
     * @param array $filters
     * @return self
     */
    public function allowedFilters(array $filters): self
    {
        $this->allowedFilters = $filters;
        return $this;
    }

    /**
     * Set allowed sorts.
     *
     * @param array $sorts
     * @return self
     */
    public function allowedSorts(array $sorts): self
    {
        $this->allowedSorts = array_merge($this->allowedSorts, $sorts);
        return $this;
    }

    /**
     * Set default sort.
     *
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function defaultSort(string $field, string $direction = 'desc'): self
    {
        $this->defaultSort = $field;
        $this->defaultDirection = $direction;
        return $this;
    }

    /**
     * Set allowed includes.
     *
     * @param array $includes
     * @return self
     */
    public function allowedIncludes(array $includes): self
    {
        $this->allowedIncludes = $includes;
        return $this;
    }

    /**
     * Set allowed fields.
     *
     * @param array $fields
     * @return self
     */
    public function allowedFields(array $fields): self
    {
        $this->allowedFields = $fields;
        return $this;
    }

    /**
     * Set searchable fields.
     *
     * @param array $fields
     * @param string $operator
     * @return self
     */
    public function searchable(array $fields, string $operator = 'OR'): self
    {
        $this->searchFields = $fields;
        $this->searchOperator = $operator;
        return $this;
    }

    /**
     * Add a filter.
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $operator
     * @return self
     */
    public function filter(string $field, mixed $value, ?string $operator = null): self
    {
        $this->filters[$field] = [
            'value' => $value,
            'operator' => $operator,
        ];
        return $this;
    }

    /**
     * Add multiple filters.
     *
     * @param array $filters
     * @return self
     */
    public function filters(array $filters): self
    {
        foreach ($filters as $field => $value) {
            $this->filter($field, $value);
        }
        return $this;
    }

    /**
     * Add a sort.
     *
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function sort(string $field, string $direction = 'asc'): self
    {
        $this->sorts[] = [
            'field' => $field,
            'direction' => $direction,
        ];
        return $this;
    }

    /**
     * Add include.
     *
     * @param string $relation
     * @return self
     */
    public function include(string $relation): self
    {
        $this->includes[] = $relation;
        return $this;
    }

    /**
     * Add multiple includes.
     *
     * @param array $includes
     * @return self
     */
    public function includes(array $includes): self
    {
        $this->includes = array_merge($this->includes, $includes);
        return $this;
    }

    /**
     * Set search term.
     *
     * @param string $search
     * @return self
     */
    public function search(string $search): self
    {
        $this->search = $search;
        return $this;
    }

    /**
     * Set fields to select.
     *
     * @param array $fields
     * @return self
     */
    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Parse filters from request.
     *
     * @param array $requestFilters
     * @return self
     */
    public function parseFilters(array $requestFilters): self
    {
        foreach ($requestFilters as $field => $value) {
            // Skip if not in allowed filters
            if (!empty($this->allowedFilters) && !in_array($field, $this->allowedFilters)) {
                continue;
            }

            // Parse filter value
            $filter = $this->parseFilterValue($value);
            $this->filter($field, $filter['value'], $filter['operator']);
        }

        return $this;
    }

    /**
     * Parse a filter value.
     *
     * @param mixed $value
     * @return array
     */
    protected function parseFilterValue(mixed $value): array
    {
        $operator = '=';

        // Check for operator prefix (e.g., "gt:10", "like:search")
        if (is_string($value) && str_contains($value, ':')) {
            $parts = explode(':', $value, 2);
            $possibleOperator = $parts[0];

            $validOperators = ['=', '!=', '>', '>=', '<', '<=', 'like', 'not like', 'in', 'not in'];
            if (in_array($possibleOperator, $validOperators)) {
                $operator = $possibleOperator;
                $value = $parts[1];
            }
        }

        // Handle array values (for IN operator)
        if (is_string($value) && str_contains($value, ',')) {
            $value = explode(',', $value);
            $operator = $operator === '=' ? 'in' : $operator;
        }

        return [
            'value' => $value,
            'operator' => $operator,
        ];
    }

    /**
     * Parse sorts from request.
     *
     * @param string|array $sorts
     * @return self
     */
    public function parseSorts(string|array $sorts): self
    {
        $sorts = is_string($sorts) ? explode(',', $sorts) : $sorts;

        foreach ($sorts as $sort) {
            $direction = 'asc';

            // Check for descending sort (prefix with -)
            if (is_string($sort) && str_starts_with($sort, '-')) {
                $sort = substr($sort, 1);
                $direction = 'desc';
            }

            // Skip if not in allowed sorts
            if (!empty($this->allowedSorts) && !in_array($sort, $this->allowedSorts)) {
                continue;
            }

            $this->sort($sort, $direction);
        }

        return $this;
    }

    /**
     * Parse includes from request.
     *
     * @param string|array $includes
     * @return self
     */
    public function parseIncludes(string|array $includes): self
    {
        $includes = is_string($includes) ? explode(',', $includes) : $includes;

        foreach ($includes as $include) {
            // Skip if not in allowed includes
            if (!empty($this->allowedIncludes) && !in_array($include, $this->allowedIncludes)) {
                continue;
            }

            $this->include($include);
        }

        return $this;
    }

    /**
     * Parse fields from request (sparse fieldsets).
     *
     * @param array $fields
     * @return self
     */
    public function parseFields(array $fields): self
    {
        foreach ($fields as $resource => $resourceFields) {
            $fieldList = is_string($resourceFields) ? explode(',', $resourceFields) : $resourceFields;

            // Validate fields
            if (!empty($this->allowedFields) && $this->allowedFields !== ['*']) {
                $fieldList = array_intersect($fieldList, $this->allowedFields);
            }

            $this->fields[$resource] = $fieldList;
        }

        return $this;
    }

    /**
     * Apply all parsed filters, sorts, includes, and search.
     *
     * @return self
     */
    public function apply(): self
    {
        $this->applyFilters()
            ->applySorts()
            ->applyIncludes()
            ->applySearch()
            ->applyFields();

        return $this;
    }

    /**
     * Apply filters to the query.
     *
     * @return self
     */
    public function applyFilters(): self
    {
        foreach ($this->filters as $field => $filter) {
            $value = $filter['value'];
            $operator = $filter['operator'] ?? '=';

            // Handle nested filters (e.g., "user.name")
            if (str_contains($field, '.')) {
                $parts = explode('.', $field);
                $relation = implode('.', array_slice($parts, 0, -1));
                $column = end($parts);

                $this->query->whereHas($relation, function ($q) use ($column, $value, $operator) {
                    $this->applyFilterOperator($q, $column, $value, $operator);
                });
            } else {
                $this->applyFilterOperator($this->query, $field, $value, $operator);
            }
        }

        return $this;
    }

    /**
     * Apply filter operator to query.
     *
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     * @param string $operator
     * @return void
     */
    protected function applyFilterOperator(Builder $query, string $column, mixed $value, string $operator): void
    {
        switch ($operator) {
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($column, $values);
                break;

            case 'not in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereNotIn($column, $values);
                break;

            case 'like':
                $query->where($column, 'LIKE', "%{$value}%");
                break;

            case 'not like':
                $query->where($column, 'NOT LIKE', "%{$value}%");
                break;

            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($column, $value);
                }
                break;

            case 'not between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween($column, $value);
                }
                break;

            default:
                $query->where($column, $operator, $value);
        }
    }

    /**
     * Apply sorts to the query.
     *
     * @return self
     */
    public function applySorts(): self
    {
        // Use provided sorts or default
        $sorts = empty($this->sorts)
            ? [['field' => $this->defaultSort, 'direction' => $this->defaultDirection]]
            : $this->sorts;

        foreach ($sorts as $sort) {
            $this->query->orderBy($sort['field'], $sort['direction']);
        }

        return $this;
    }

    /**
     * Apply includes to the query.
     *
     * @return self
     */
    public function applyIncludes(): self
    {
        if (!empty($this->includes)) {
            $this->query->with(array_unique($this->includes));
        }

        return $this;
    }

    /**
     * Apply search to the query.
     *
     * @return self
     */
    public function applySearch(): self
    {
        if ($this->search && !empty($this->searchFields)) {
            $this->query->where(function ($query) {
                foreach ($this->searchFields as $field) {
                    $method = $this->searchOperator === 'OR' ? 'orWhere' : 'andWhere';
                    $query->{$method}($field, 'LIKE', "%{$this->search}%");
                }
            });
        }

        return $this;
    }

    /**
     * Apply field selection to the query.
     *
     * @return self
     */
    public function applyFields(): self
    {
        if (!empty($this->fields)) {
            // Handle resource-specific fields (e.g., fields[posts]=title,content)
            if (isset($this->fields['*']) || isset($this->fields[$this->getModelName()])) {
                $fields = $this->fields['*'] ?? $this->fields[$this->getModelName()] ?? ['*'];
                $this->query->select($fields);
            }
        }

        return $this;
    }

    /**
     * Get the model name from the query.
     *
     * @return string
     */
    protected function getModelName(): string
    {
        return Str::plural(Str::snake(class_basename($this->query->getModel())));
    }

    /**
     * Get paginated results.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->query->paginate($perPage);
    }

    /**
     * Get all results.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query->get();
    }

    /**
     * Get first result.
     *
     * @return Model|null
     */
    public function first(): ?Model
    {
        return $this->query->first();
    }

    /**
     * Get count.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Add a where condition.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->where(...func_get_args());
        return $this;
    }

    /**
     * Add a whereIn condition.
     *
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Add a whereBetween condition.
     *
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->query->whereBetween($column, $values);
        return $this;
    }

    /**
     * Add a with condition (eager loading).
     *
     * @param string|array $relations
     * @param callable|null $callback
     * @return self
     */
    public function with(string|array $relations, ?callable $callback = null): self
    {
        $this->query->with($relations, $callback);
        return $this;
    }

    /**
     * Add a join.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @param bool $where
     * @return self
     */
    public function join(string $table, string $first, string $operator = null, string $second = null, string $type = 'inner', bool $where = false): self
    {
        $this->query->join(...func_get_args());
        return $this;
    }

    /**
     * Add a groupBy.
     *
     * @param array|string $groups
     * @return self
     */
    public function groupBy(array|string $groups): self
    {
        $this->query->groupBy($groups);
        return $this;
    }

    /**
     * Add a having.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function having(string $column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->having(...func_get_args());
        return $this;
    }

    /**
     * Add a limit.
     *
     * @param int $value
     * @return self
     */
    public function limit(int $value): self
    {
        $this->query->limit($value);
        return $this;
    }

    /**
     * Add an offset.
     *
     * @param int $value
     * @return self
     */
    public function offset(int $value): self
    {
        $this->query->offset($value);
        return $this;
    }

    /**
     * Add a distinct clause.
     *
     * @return self
     */
    public function distinct(): self
    {
        $this->query->distinct();
        return $this;
    }

    /**
     * Add a withCount clause.
     *
     * @param string|array $relations
     * @return self
     */
    public function withCount(string|array $relations): self
    {
        $this->query->withCount($relations);
        return $this;
    }

    /**
     * Add a withSum clause.
     *
     * @param string $relation
     * @param string $column
     * @return self
     */
    public function withSum(string $relation, string $column): self
    {
        $this->query->withSum($relation, $column);
        return $this;
    }

    /**
     * Add a withAvg clause.
     *
     * @param string $relation
     * @param string $column
     * @return self
     */
    public function withAvg(string $relation, string $column): self
    {
        $this->query->withAvg($relation, $column);
        return $this;
    }

    /**
     * Add a scope.
     *
     * @param string $scope
     * @param array $parameters
     * @return self
     */
    public function scope(string $scope, array $parameters = []): self
    {
        $this->query->{$scope}(...$parameters);
        return $this;
    }

    /**
     * Apply request parameters (filters, sorts, includes, search).
     *
     * @param array $request
     * @return self
     */
    public function fromRequest(array $request): self
    {
        // Parse filters
        if (isset($request['filter']) && is_array($request['filter'])) {
            $this->parseFilters($request['filter']);
        }

        // Parse sorts
        if (isset($request['sort'])) {
            $this->parseSorts($request['sort']);
        }

        // Parse includes
        if (isset($request['include'])) {
            $this->parseIncludes($request['include']);
        }

        // Parse search
        if (isset($request['search'])) {
            $this->search($request['search']);
        }

        // Parse fields
        if (isset($request['fields']) && is_array($request['fields'])) {
            $this->parseFields($request['fields']);
        }

        return $this;
    }
}
