<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Services\AI\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:use_ai');
    }

    public function index(): View
    {
        $history = AiGeneration::with('user')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.ai.index', compact('history'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', 'in:article,tutorial,review,news,title,meta_description,keywords,summary,expansion,tags,category,audit'],
        ]);

        $content = app(AIService::class)->generateContent($validated['prompt'], $validated['type']);

        AiGeneration::create([
            'user_id' => auth()->id(),
            'model_name' => config('services.nvidia.model', 'mixtral-8x7b-instruct-v0.1'),
            'prompt' => $validated['prompt'],
            'generated_content' => $content,
            'generation_type' => $validated['type'],
            'token_usage' => 0,
        ]);

        return redirect()->back()->with('success', 'Content generated successfully.');
    }

    public function history(): View
    {
        return $this->index();
    }
}
