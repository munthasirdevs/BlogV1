<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Class RecursiveParent
 *
 * Custom validation rule to prevent circular hierarchy in parent-child relationships.
 * Ensures that a record cannot be its own descendant.
 *
 * Usage:
 *   new RecursiveParent('categories', 'parent_id')
 */
class RecursiveParent implements ValidationRule, DataAwareRule
{
    /**
     * The table to check against.
     */
    protected string $table;

    /**
     * The parent column name.
     */
    protected string $parentColumn;

    /**
     * The ID column name.
     */
    protected string $idColumn;

    /**
     * The current record ID being updated.
     */
    protected ?int $currentId = null;

    /**
     * Maximum depth to check (prevents infinite loops).
     */
    protected int $maxDepth = 20;

    /**
     * The data under validation.
     */
    protected array $data = [];

    /**
     * Create a new rule instance.
     *
     * @param string $table The database table name
     * @param string $parentColumn The parent column name (default: 'parent_id')
     * @param string $idColumn The ID column name (default: 'id')
     */
    public function __construct(
        string $table,
        string $parentColumn = 'parent_id',
        string $idColumn = 'id'
    ) {
        $this->table = $table;
        $this->parentColumn = $parentColumn;
        $this->idColumn = $idColumn;
    }

    /**
     * Set the current record ID (for updates).
     *
     * @param int $id
     * @return self
     */
    public function exclude(int $id): self
    {
        $this->currentId = $id;
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
        // If no parent is being set, validation passes
        if (empty($value)) {
            return;
        }

        $parentId = (int) $value;

        // Cannot set self as parent
        if ($this->currentId !== null && $parentId === $this->currentId) {
            $fail('A record cannot be its own parent.');
            return;
        }

        // Check for circular reference by traversing up the tree
        if ($this->hasCircularReference($parentId)) {
            $fail('Invalid parent selected. This would create a circular reference in the hierarchy.');
        }
    }

    /**
     * Check if setting the given parent would create a circular reference.
     *
     * @param int $parentId
     * @return bool
     */
    protected function hasCircularReference(int $parentId): bool
    {
        // If we're updating a record, the new parent cannot be a descendant of the current record
        if ($this->currentId === null) {
            return false;
        }

        $visited = [];
        $currentParentId = $parentId;
        $depth = 0;

        while ($currentParentId !== null && $depth < $this->maxDepth) {
            // If we encounter the current record ID, it's a circular reference
            if ($currentParentId === $this->currentId) {
                return true;
            }

            // Prevent infinite loops
            if (in_array($currentParentId, $visited, true)) {
                break;
            }

            $visited[] = $currentParentId;

            // Get the parent of the current parent
            $result = DB::table($this->table)
                ->where($this->idColumn, $currentParentId)
                ->value($this->parentColumn);

            $currentParentId = $result !== null ? (int) $result : null;
            $depth++;
        }

        return false;
    }

    /**
     * Get all descendants of a given ID.
     *
     * @param int $id
     * @return array
     */
    public function getDescendants(int $id): array
    {
        $descendants = [];
        $toCheck = [$id];
        $depth = 0;

        while (!empty($toCheck) && $depth < $this->maxDepth) {
            $currentId = array_shift($toCheck);

            $children = DB::table($this->table)
                ->where($this->parentColumn, $currentId)
                ->pluck($this->idColumn)
                ->toArray();

            foreach ($children as $childId) {
                if (!in_array($childId, $descendants, true)) {
                    $descendants[] = (int) $childId;
                    $toCheck[] = (int) $childId;
                }
            }

            $depth++;
        }

        return $descendants;
    }
}
