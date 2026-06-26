<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService,
        protected CacheService $cacheService
    ) {
        $this->middleware('permission:edit_tags');
    }

    public function index(Request $request): View
    {
        $query = Tag::with('creator');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->get('status') === 'hidden') {
            $query->where('status', 'hidden');
        } elseif ($request->get('status') === 'active') {
            $query->where('status', 'active');
        }

        $sort = $request->get('sort', 'usage');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'trending' => $query->orderBy('trending_score', 'desc'),
            'created' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('usage_count', 'desc'),
        };

        $tags = $query->paginate(30)->withQueryString();
        $totalTags = Tag::count();
        $activeTags = Tag::active()->count();
        $maxUsage = Tag::max('usage_count') ?: 1;

        return view('admin.tags.index', compact('tags', 'totalTags', 'activeTags', 'maxUsage'));
    }

    public function create(): View
    {
        return view('admin.tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['created_by'] = auth()->id();
        $tag = Tag::create($validated);

        $tag->generateSeo();

        activity()->performedOn($tag)->causedBy(auth()->user())->log('created tag: ' . $tag->name);
        $this->tagService->invalidateCache();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function show(Tag $tag): View
    {
        $tag->load('creator');
        $postCount = $tag->posts()->count();
        $recentPosts = $tag->posts()->with('category')->latest()->take(5)->get();
        return view('admin.tags.show', compact('tag', 'postCount', 'recentPosts'));
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug,' . $tag->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['updated_by'] = auth()->id();
        $tag->update($validated);

        if ($request->boolean('update_seo')) {
            $tag->generateSeo();
        }

        activity()->performedOn($tag)->causedBy(auth()->user())
            ->withProperties(['changes' => $tag->getChanges()])
            ->log('updated tag: ' . $tag->name);

        $this->tagService->invalidateCache();

        return redirect()->route('admin.tags.edit', $tag)
            ->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        activity()->performedOn($tag)->causedBy(auth()->user())->log('deleted tag: ' . $tag->name);
        $this->tagService->invalidateCache();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag deleted.');
    }

    public function restore(int $id): RedirectResponse
    {
        $tag = Tag::withTrashed()->findOrFail($id);
        $tag->restore();
        $this->tagService->invalidateCache();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag restored.');
    }

    public function merge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_id' => ['required', 'exists:tags,id'],
            'target_id' => ['required', 'exists:tags,id', 'different:source_id'],
        ]);

        $target = $this->tagService->merge($validated['source_id'], $validated['target_id']);

        activity()->causedBy(auth()->user())->log("merged tags: source#{$validated['source_id']} into '{$target->name}'");

        return redirect()->route('admin.tags.edit', $target)
            ->with('success', 'Tags merged successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'No tags selected.');

        Tag::whereIn('id', $ids)->each(function ($tag) {
            $tag->posts()->detach();
            $tag->delete();
        });

        $this->tagService->invalidateCache();
        return back()->with('success', count($ids) . ' tags deleted.');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'active');

        Tag::whereIn('id', $ids)->update(['status' => $status]);
        $this->tagService->invalidateCache();

        return back()->with('success', count($ids) . ' tags updated.');
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tags-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Slug', 'Description', 'Color', 'Status']);

            Tag::chunk(100, function ($tags) use ($handle) {
                foreach ($tags as $tag) {
                    fputcsv($handle, [$tag->name, $tag->slug, $tag->description ?? '', $tag->color ?? '', $tag->status]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map('trim', $header);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_map('trim', $row));
            $slug = $data['Slug'] ?? Str::slug($data['Name'] ?? 'tag-' . Str::random(6));

            if (Tag::where('slug', $slug)->exists()) continue;

            Tag::create([
                'name' => $data['Name'] ?? 'Untitled',
                'slug' => $slug,
                'description' => $data['Description'] ?? null,
                'color' => $data['Color'] ?? null,
                'status' => $data['Status'] ?? 'active',
                'created_by' => auth()->id(),
            ]);
            $count++;
        }
        fclose($handle);

        $this->tagService->invalidateCache();
        return redirect()->route('admin.tags.index')->with('success', "Imported {$count} tags.");
    }

    public function autocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        $term = $request->get('q', '');
        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $tags = Tag::active()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('usage_count', 'desc')
            ->take(10)
            ->get(['id', 'name', 'slug', 'usage_count']);

        return response()->json($tags);
    }

    public function recalculateTrending(): RedirectResponse
    {
        $this->tagService->recalculateTrending();
        return redirect()->route('admin.tags.index')->with('success', 'Trending scores recalculated.');
    }
}
