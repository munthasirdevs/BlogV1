<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CacheService $cacheService
    ) {
        $this->middleware('permission:edit_categories');
    }

    public function index(Request $request): View
    {
        $query = Category::with('parent', 'creator');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $categories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tree = Category::tree();

        return view('admin.categories.index', compact('categories', 'tree'));
    }

    public function create(): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($validated['parent_id'] ?? null) {
            $this->validateNoCircularReference($validated['parent_id'], null);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $this->uploadAndOptimizeImage($request->file('image'));
        }

        $validated['created_by'] = auth()->id();
        $category = Category::create($validated);

        $category->generateSeo();

        activity()
            ->performedOn($category)
            ->causedBy(auth()->user())
            ->log('created category: ' . $category->name);

        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $category->load('parent', 'creator', 'children');
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $category->load('parent', 'creator', 'children');

        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        if (($validated['parent_id'] ?? null) && $validated['parent_id'] != $category->id) {
            $this->validateNoCircularReference($validated['parent_id'], $category->id);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $this->uploadAndOptimizeImage($request->file('image'));
        } elseif (!empty($validated['remove_image'])) {
            $validated['image'] = null;
        } else {
            unset($validated['image']);
        }
        unset($validated['remove_image']);

        $validated['updated_by'] = auth()->id();

        $category->update($validated);

        if ($request->boolean('update_seo')) {
            $category->generateSeo();
        }

        activity()
            ->performedOn($category)
            ->causedBy(auth()->user())
            ->withProperties(['changes' => $category->getChanges()])
            ->log('updated category: ' . $category->name);

        if ($category->wasChanged('parent_id')) {
            $oldParent = $category->getOriginal('parent_id') ? Category::find($category->getOriginal('parent_id'))?->name : 'none';
            $newParent = $category->parent?->name ?? 'none';
            activity()
                ->performedOn($category)
                ->causedBy(auth()->user())
                ->withProperties(['old_parent' => $oldParent, 'new_parent' => $newParent])
                ->log("moved category '{$category->name}' from '{$oldParent}' to '{$newParent}'");
        }

        if ($request->boolean('update_seo')) {
            activity()
                ->performedOn($category)
                ->causedBy(auth()->user())
                ->log('updated SEO for category: ' . $category->name);
        }

        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.edit', $category)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $postCount = $category->posts()->count();
        if ($postCount > 0) {
            $category->posts()->update(['category_id' => null]);
        }

        $category->children()->update(['parent_id' => null]);
        $category->delete();

        activity()
            ->performedOn($category)
            ->causedBy(auth()->user())
            ->log('deleted category: ' . $category->name);

        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted. ' . $postCount . ' posts uncategorized.');
    }

    public function restore(int $id): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category restored successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No categories selected.');
        }

        $categories = Category::whereIn('id', $ids)->get();
        foreach ($categories as $category) {
            $category->posts()->update(['category_id' => null]);
            $category->children()->update(['parent_id' => null]);
            $category->delete();
        }

        $this->cacheService->invalidateCategories();

        return back()->with('success', count($ids) . ' categories deleted.');
    }

    public function duplicate(int $id): RedirectResponse
    {
        $original = Category::findOrFail($id);
        $copy = $original->replicate();
        $copy->name = $original->name . ' (Copy)';
        $copy->slug = $original->slug . '-copy-' . Str::random(4);
        $copy->created_by = auth()->id();
        $copy->save();

        $copy->generateSeo();

        activity()->performedOn($copy)->causedBy(auth()->user())->log('duplicated category from: ' . $original->name);
        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.edit', $copy)
            ->with('success', 'Category duplicated.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No categories selected.');
        }
        Category::withTrashed()->whereIn('id', $ids)->restore();
        $this->cacheService->invalidateCategories();
        return back()->with('success', count($ids) . ' categories restored.');
    }

    public function bulkMove(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $parentId = $request->input('parent_id');

        if (empty($ids)) {
            return back()->with('error', 'No categories selected.');
        }

        foreach ($ids as $id) {
            if ($id == $parentId) continue;
            $cat = Category::find($id);
            if ($cat) {
                $cat->parent_id = $parentId ?: null;
                $cat->save();
            }
        }

        $this->cacheService->invalidateCategories();
        return back()->with('success', count($ids) . ' categories moved.');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'published');

        if (empty($ids) || !in_array($status, ['draft', 'published', 'archived', 'hidden'])) {
            return back()->with('error', 'Invalid request.');
        }

        Category::whereIn('id', $ids)->update(['status' => $status]);

        $this->cacheService->invalidateCategories();

        return back()->with('success', count($ids) . ' categories updated to ' . $status . '.');
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="categories-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Slug', 'Parent', 'Description', 'Status', 'Sort Order', 'Color', 'Icon', 'Featured']);

            Category::with('parent')->chunk(100, function ($categories) use ($handle) {
                foreach ($categories as $cat) {
                    fputcsv($handle, [
                        $cat->name,
                        $cat->slug,
                        $cat->parent?->name ?? '',
                        $cat->short_description ?? '',
                        $cat->status,
                        $cat->sort_order,
                        $cat->color ?? '',
                        $cat->icon ?? '',
                        $cat->featured ? 'Yes' : 'No',
                    ]);
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

            $slug = $data['Slug'] ?? Str::slug($data['Name'] ?? 'category-' . Str::random(8));

            if (Category::where('slug', $slug)->exists()) {
                continue;
            }

            $parentId = null;
            if (!empty($data['Parent'])) {
                $parent = Category::where('name', $data['Parent'])->first();
                $parentId = $parent?->id;
            }

            Category::create([
                'name' => $data['Name'] ?? 'Untitled',
                'slug' => $slug,
                'parent_id' => $parentId,
                'short_description' => $data['Description'] ?? null,
                'status' => in_array($data['Status'] ?? '', ['draft', 'published', 'archived', 'hidden']) ? $data['Status'] : 'draft',
                'sort_order' => (int) ($data['Sort Order'] ?? 0),
                'color' => $data['Color'] ?? null,
                'icon' => $data['Icon'] ?? null,
                'featured' => ($data['Featured'] ?? 'No') === 'Yes',
                'created_by' => auth()->id(),
            ]);
            $count++;
        }
        fclose($handle);

        $this->cacheService->invalidateCategories();

        return redirect()->route('admin.categories.index')
            ->with('success', "Imported {$count} categories.");
    }

    private function uploadAndOptimizeImage(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::uuid()->toString() . '.webp';
        $path = 'categories/' . $filename;

        try {
            $manager = app(ImageManager::class);
            $image = $manager->read($file->getRealPath());
            $image->toWebp(80)->save(Storage::disk('public')->path($path));

            $thumbnailPath = 'categories/' . Str::uuid()->toString() . '_thumb.webp';
            $thumb = $manager->read($file->getRealPath());
            $thumb->cover(150, 150)->toWebp(70)->save(Storage::disk('public')->path($thumbnailPath));

            return Storage::url($path);
        } catch (\Exception $e) {
            Log::warning('Category image optimization failed, storing original', ['error' => $e->getMessage()]);
            $fallback = $file->store('categories', 'public');
            return Storage::url($fallback);
        }
    }

    private function validateNoCircularReference(int $parentId, ?int $currentId): void
    {
        $visited = [$currentId];
        $parent = Category::find($parentId);

        while ($parent) {
            if (in_array($parent->id, $visited)) {
                abort(422, 'Circular reference detected: a category cannot be its own ancestor.');
            }
            $visited[] = $parent->id;
            $parent = $parent->parent;
        }
    }
}
