<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::active()->orderBy('usage_count', 'desc')->get();
        return response()->json(['data' => $tags]);
    }

    public function show(Tag $tag): JsonResponse
    {
        if ($tag->status !== 'active') {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json(['data' => $tag]);
    }
}
