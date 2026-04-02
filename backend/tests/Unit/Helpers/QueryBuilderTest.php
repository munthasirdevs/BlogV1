<?php

namespace Tests\Unit\Helpers;

use App\Helpers\QueryBuilder;
use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class QueryBuilderTest
 *
 * Tests for the QueryBuilder helper class.
 */
class QueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        Post::create([
            'title' => 'Laravel Tips',
            'slug' => 'laravel-tips',
            'content' => 'Content here',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'views_count' => 100,
        ]);

        Post::create([
            'title' => 'PHP Best Practices',
            'slug' => 'php-best-practices',
            'content' => 'Content here',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'views_count' => 50,
        ]);
    }

    public function test_basic_query(): void
    {
        $results = QueryBuilder::for(Post::class)->get();

        $this->assertCount(2, $results);
    }

    public function test_filter_by_field(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->filter('status', 'published')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('published', $results->first()->status);
    }

    public function test_filter_with_operator(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->filter('views_count', 75, '>')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(100, $results->first()->views_count);
    }

    public function test_parse_filters(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedFilters(['status', 'views_count'])
            ->parseFilters([
                'status' => 'published',
                'views_count' => 'gt:75',
            ])
            ->get();

        $this->assertCount(1, $results);
    }

    public function test_sort_ascending(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->sort('views_count', 'asc')
            ->get();

        $this->assertEquals(50, $results->first()->views_count);
    }

    public function test_sort_descending(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->sort('views_count', 'desc')
            ->get();

        $this->assertEquals(100, $results->first()->views_count);
    }

    public function test_parse_sorts(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedSorts(['views_count', 'created_at'])
            ->parseSorts('-views_count')
            ->get();

        $this->assertEquals(100, $results->first()->views_count);
    }

    public function test_default_sort(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->defaultSort('views_count', 'desc')
            ->get();

        $this->assertEquals(100, $results->first()->views_count);
    }

    public function test_search(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->searchable(['title', 'content'])
            ->search('Laravel')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Tips', $results->first()->title);
    }

    public function test_include_relationships(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedIncludes(['author', 'category'])
            ->includes(['author', 'category'])
            ->get();

        $this->assertCount(2, $results);
        $this->assertNotNull($results->first()->author);
        $this->assertNotNull($results->first()->category);
    }

    public function test_parse_includes(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedIncludes(['author', 'category', 'tags'])
            ->parseIncludes('author,category')
            ->get();

        $this->assertCount(2, $results);
        $this->assertNotNull($results->first()->author);
    }

    public function test_paginate(): void
    {
        $paginator = QueryBuilder::for(Post::class)
            ->paginate(1);

        $this->assertEquals(1, $paginator->perPage());
        $this->assertEquals(2, $paginator->lastPage());
    }

    public function test_select_fields(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->select(['id', 'title', 'slug'])
            ->get();

        $this->assertNotNull($results->first()->id);
        $this->assertNotNull($results->first()->title);
    }

    public function test_where(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->where('status', 'published')
            ->get();

        $this->assertCount(1, $results);
    }

    public function test_whereIn(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->whereIn('status', ['published', 'draft'])
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_withCount(): void
    {
        $results = QueryBuilder::for(Category::class)
            ->withCount('posts')
            ->get();

        $this->assertNotNull($results->first()->posts_count);
    }

    public function test_limit(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->limit(1)
            ->get();

        $this->assertCount(1, $results);
    }

    public function test_from_request(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedFilters(['status'])
            ->allowedSorts(['views_count'])
            ->allowedIncludes(['author'])
            ->fromRequest([
                'filter' => ['status' => 'published'],
                'sort' => '-views_count',
                'include' => 'author',
            ])
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(100, $results->first()->views_count);
        $this->assertNotNull($results->first()->author);
    }

    public function test_nested_filter(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedFilters(['author.name'])
            ->filter('author.name', 'Test User')
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_count(): void
    {
        $count = QueryBuilder::for(Post::class)
            ->filter('status', 'published')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_first(): void
    {
        $post = QueryBuilder::for(Post::class)
            ->filter('status', 'published')
            ->first();

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('published', $post->status);
    }

    public function test_allowed_filters_validation(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedFilters(['status'])
            ->parseFilters([
                'status' => 'published',
                'unauthorized_field' => 'value',
            ])
            ->get();

        $this->assertCount(1, $results);
    }

    public function test_allowed_sorts_validation(): void
    {
        $results = QueryBuilder::for(Post::class)
            ->allowedSorts(['views_count'])
            ->parseSorts(['views_count', 'unauthorized_field'])
            ->get();

        $this->assertCount(2, $results);
    }
}
