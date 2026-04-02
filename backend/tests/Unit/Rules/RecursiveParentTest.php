<?php

namespace Tests\Unit\Rules;

use App\Rules\RecursiveParent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class RecursiveParentTest
 *
 * Tests for the RecursiveParent validation rule.
 */
class RecursiveParentTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_null_parent(): void
    {
        $rule = new RecursiveParent('categories', 'parent_id');
        
        $validator = validator(
            ['parent_id' => null],
            ['parent_id' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_setting_self_as_parent(): void
    {
        $category = \App\Models\Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $rule = (new RecursiveParent('categories', 'parent_id'))->exclude($category->id);
        
        $validator = validator(
            ['parent_id' => $category->id],
            ['parent_id' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('cannot be its own parent', $validator->errors()->first('parent_id'));
    }

    public function test_validation_passes_with_valid_parent(): void
    {
        $parent = \App\Models\Category::create([
            'name' => 'Parent Category',
            'slug' => 'parent-category',
        ]);

        $rule = new RecursiveParent('categories', 'parent_id');
        
        $validator = validator(
            ['parent_id' => $parent->id],
            ['parent_id' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_circular_reference(): void
    {
        // Create parent category
        $parent = \App\Models\Category::create([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);

        // Create child category with parent
        $child = \App\Models\Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        // Try to set parent's parent to child (circular reference)
        $rule = (new RecursiveParent('categories', 'parent_id'))->exclude($parent->id);
        
        $validator = validator(
            ['parent_id' => $child->id],
            ['parent_id' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('circular reference', $validator->errors()->first('parent_id'));
    }

    public function test_validation_passes_with_non_existent_parent_id(): void
    {
        $rule = new RecursiveParent('categories', 'parent_id');
        
        $validator = validator(
            ['parent_id' => 99999],
            ['parent_id' => $rule]
        );

        // The rule itself passes, but the exists validation would fail
        $this->assertTrue($validator->passes());
    }

    public function test_getDescendants_returns_all_descendants(): void
    {
        // Create hierarchy: Grandparent -> Parent -> Child
        $grandparent = \App\Models\Category::create([
            'name' => 'Grandparent',
            'slug' => 'grandparent',
        ]);

        $parent = \App\Models\Category::create([
            'name' => 'Parent',
            'slug' => 'parent',
            'parent_id' => $grandparent->id,
        ]);

        $child = \App\Models\Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $rule = new RecursiveParent('categories', 'parent_id');
        $descendants = $rule->getDescendants($grandparent->id);

        $this->assertCount(2, $descendants);
        $this->assertContains($parent->id, $descendants);
        $this->assertContains($child->id, $descendants);
    }
}
