<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(
        protected AIService $aiService
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', 'in:article,tutorial,review,news,title,meta_description,keywords,summary,expansion,tags,category,audit'],
        ]);

        $content = $this->aiService->generateContent($validated['prompt'], $validated['type']);

        AiGeneration::create([
            'user_id' => auth()->id(),
            'model_name' => config('services.nvidia.model', 'mixtral-8x7b-instruct-v0.1'),
            'prompt' => $validated['prompt'],
            'generated_content' => $content,
            'generation_type' => $validated['type'],
            'token_usage' => 0,
        ]);

        return response()->json(['success' => true, 'data' => ['content' => $content]]);
    }
}
