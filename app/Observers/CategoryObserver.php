<?php

namespace App\Observers;

use App\Models\Category;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $category->updateQuietly(['posts_count' => $category->posts()->count()]);
    }

    public function deleted(Category $category): void
    {
        // No action needed — category is gone, posts_count is meaningless
    }
}
