<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::published()->withCount('posts')->orderBy('sort_order')->get();
        return response()->json(['data' => $categories]);
    }

    public function show(Category $category): JsonResponse
    {
        if ($category->status !== 'published') {
            return response()->json(['error' => 'Not found'], 404);
        }
        $category->loadCount('posts');
        return response()->json(['data' => $category]);
    }
}
