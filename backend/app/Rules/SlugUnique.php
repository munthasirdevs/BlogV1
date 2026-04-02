<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Class SlugUnique
 *
 * Custom validation rule to check if a slug is unique within a table,
 * excluding the current record during updates.
 *
 * Usage:
 *   new SlugUnique('posts', 'slug')
 *   new SlugUnique('categories', 'slug', $this->route('category'))
 */
class SlugUnique implements ValidationRule, DataAwareRule
{
    /**
     * The table to check against.
     */
    protected string $table;

    /**
     * The slug column name.
     */
    protected string $column;

    /**
     * The current record ID to exclude (for updates).
     */
    protected ?int $excludeId = null;

    /**
     * Additional where conditions.
     */
    protected array $where = [];

    /**
     * The data under validation.
     */
    protected array $data = [];

    /**
     * Create a new rule instance.
     *
     * @param string $table The database table name
     * @param string $column The slug column name (default: 'slug')
     * @param mixed $excludeId Model or ID to exclude from uniqueness check
     */
    public function __construct(
        string $table,
        string $column = 'slug',
        mixed $excludeId = null
    ) {
        $this->table = $table;
        $this->column = $column;

        // Handle model instance or direct ID
        if ($excludeId instanceof \Illuminate\Database\Eloquent\Model) {
            $this->excludeId = $excludeId->getKey();
        } elseif (is_numeric($excludeId)) {
            $this->excludeId = (int) $excludeId;
        }
    }

    /**
     * Set additional where conditions.
     *
     * @param array $where
     * @return self
     */
    public function where(array $where): self
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Set the data under validation.
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $query = DB::table($this->table)
            ->where($this->column, $value);

        // Exclude current record for updates
        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        // Apply additional where conditions
        foreach ($this->where as $field => $val) {
            // Support getting value from validation data
            if (is_string($val) && str_starts_with($val, '{') && str_ends_with($val, '}')) {
                $dataKey = trim($val, '{}');
                $val = $this->data[$dataKey] ?? null;
            }

            if ($val !== null) {
                $query->where($field, $val);
            }
        }

        // Check for soft deleted records
        if (DB::getSchemaBuilder()->hasColumn($this->table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($query->exists()) {
            $fail("The {$this->column} has already been taken.");
        }
    }
}
