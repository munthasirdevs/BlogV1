<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Services\AI\AIService;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function __construct(
        protected CacheService $cacheService,
        protected AIService $aiService
    ) {
        $this->middleware('permission:edit_posts');
    }

    public function index(Request $request): View
    {
        $query = Post::with('author', 'category');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $sort = $request->get('sort', 'created');
        match ($sort) {
            'views' => $query->orderBy('views_count', 'desc'),
            'published' => $query->orderBy('published_at', 'desc'),
            'updated' => $query->orderBy('updated_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $posts = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.posts.create', compact('categories', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'content_format' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:draft,review,seo_review,approved,scheduled,published,archived,revision_required'],
            'visibility' => ['nullable', 'in:public,private,unlisted'],
            'is_featured' => ['nullable', 'boolean'],
            'is_scheduled' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Post::generateUniqueSlug($validated['title']);
        $validated['excerpt'] = $validated['excerpt'] ?: strip_tags(mb_substr($validated['content'], 0, 160));
        $validated['author_id'] = auth()->id();

        $post = Post::create($validated);

        if (!empty($validated['tags'])) {
            $tagData = [];
            foreach ($validated['tags'] as $tagId) {
                $tagData[$tagId] = ['relevance_score' => 0, 'created_at' => now()];
            }
            $post->tags()->sync($tagData);
        }

        $revisionNumber = PostRevision::where('post_id', $post->id)->max('revision_number') ?? 0;
        PostRevision::create([
            'post_id' => $post->id,
            'editor_id' => auth()->id(),
            'revision_number' => $revisionNumber + 1,
            'title_snapshot' => $post->title,
            'excerpt_snapshot' => $post->excerpt,
            'content_snapshot' => $post->content,
            'change_summary' => 'Initial creation',
        ]);

        activity()->performedOn($post)->causedBy(auth()->user())->log('created post: ' . $post->title);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post created successfully.');
    }

    public function show(Post $post): View
    {
        $post->load('author', 'category', 'tags', 'revisions', 'seo', 'metrics');
        return view('admin.posts.show', compact('post'));
    }

    public function edit(Post $post): View
    {
        $post->load('author', 'category', 'tags', 'revisions', 'seo', 'metrics');
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:posts,slug,' . $post->id],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'content_format' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:draft,review,seo_review,approved,scheduled,published,archived,revision_required'],
            'visibility' => ['nullable', 'in:public,private,unlisted'],
            'is_featured' => ['nullable', 'boolean'],
            'is_scheduled' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $validated['excerpt'] = $validated['excerpt'] ?: strip_tags(mb_substr($validated['content'], 0, 160));
        $post->update($validated);

        if (isset($validated['tags'])) {
            $tagData = [];
            foreach ($validated['tags'] as $tagId) {
                $tagData[$tagId] = ['relevance_score' => 0, 'created_at' => now()];
            }
            $post->tags()->sync($tagData);
        }

        if ($post->wasChanged('content') || $post->wasChanged('title') || $post->wasChanged('excerpt')) {
            $revisionNumber = PostRevision::where('post_id', $post->id)->max('revision_number') ?? 0;
            PostRevision::create([
                'post_id' => $post->id,
                'editor_id' => auth()->id(),
                'revision_number' => $revisionNumber + 1,
                'title_snapshot' => $post->title,
                'excerpt_snapshot' => $post->excerpt,
                'content_snapshot' => $post->content,
                'change_summary' => 'Updated via editor',
            ]);
        }

        activity()->performedOn($post)->causedBy(auth()->user())
            ->withProperties(['changes' => $post->getChanges()])
            ->log('updated post: ' . $post->title);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();
        activity()->performedOn($post)->causedBy(auth()->user())->log('deleted post: ' . $post->title);
        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }

    public function restore(int $id): RedirectResponse
    {
        $post = Post::withTrashed()->findOrFail($id);
        $post->restore();
        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post restored.');
    }

    public function duplicate(Post $post): RedirectResponse
    {
        $original = $post->load('tags');
        $copy = $original->duplicate(auth()->id());

        $revisionNumber = PostRevision::where('post_id', $copy->id)->max('revision_number') ?? 0;
        PostRevision::create([
            'post_id' => $copy->id,
            'editor_id' => auth()->id(),
            'revision_number' => $revisionNumber + 1,
            'title_snapshot' => $copy->title,
            'excerpt_snapshot' => $copy->excerpt,
            'content_snapshot' => $copy->content,
            'change_summary' => 'Duplicated from post #' . $original->id,
        ]);

        activity()->performedOn($copy)->causedBy(auth()->user())->log('duplicated post from: ' . $original->title);

        return redirect()->route('admin.posts.edit', $copy)->with('success', 'Post duplicated.');
    }

    public function reject(Request $request, Post $post): RedirectResponse
    {
        $reason = $request->input('reason', 'No reason provided');

        if (!in_array($post->status, ['review', 'seo_review'])) {
            return back()->with('error', 'Post is not in a reviewable state.');
        }

        $post->update([
            'status' => 'revision_required',
            'change_summary' => 'Rejected: ' . $reason,
        ]);

        $authors = \App\Models\User::whereIn('id', [$post->author_id])->get();
        \Illuminate\Support\Facades\Notification::send($authors, new \App\Notifications\ContentApprovalNotification($post, 'rejected'));

        activity()->performedOn($post)->causedBy(auth()->user())->log('rejected post: ' . $post->title . ' - ' . $reason);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post rejected. Author notified.');
    }

    public function approve(Post $post): RedirectResponse
    {
        $nextStatus = match ($post->status) {
            'review' => 'seo_review',
            'seo_review' => 'approved',
            default => null,
        };

        if (!$nextStatus) {
            return back()->with('error', 'Post cannot be approved from current state.');
        }

        $post->update(['status' => $nextStatus]);

        activity()->performedOn($post)->causedBy(auth()->user())->log('approved post: ' . $post->title);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post moved to ' . str_replace('_', ' ', $nextStatus) . '.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'No posts selected.');
        Post::whereIn('id', $ids)->each(fn($p) => $p->delete());
        return back()->with('success', count($ids) . ' posts deleted.');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'draft');
        Post::whereIn('id', $ids)->update(['status' => $status]);
        return back()->with('success', count($ids) . ' posts updated to ' . $status . '.');
    }

    public function bulkFeature(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $featured = $request->boolean('featured');
        Post::whereIn('id', $ids)->update(['is_featured' => $featured]);
        return back()->with('success', count($ids) . ' posts ' . ($featured ? 'featured' : 'unfeatured') . '.');
    }

    public function bulkSchedule(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $scheduledAt = $request->input('scheduled_at');
        $timezone = $request->input('timezone', config('app.timezone'));

        if (empty($ids) || empty($scheduledAt)) {
            return back()->with('error', 'Select posts and a schedule date.');
        }

        $dateTime = \Carbon\Carbon::parse($scheduledAt, $timezone);

        $count = 0;
        foreach ($ids as $id) {
            $post = Post::find($id);
            if ($post && $post->status === 'draft') {
                $post->schedule($dateTime, $timezone);
                $count++;
            }
        }

        return back()->with('success', $count . ' posts scheduled for ' . $dateTime->format('M d, Y g:i A') . ' (' . $timezone . ').');
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="posts-' . now()->format('Y-m-d') . '.csv"'];
        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Title', 'Slug', 'Status', 'Category', 'Tags', 'Excerpt', 'Content', 'Visibility', 'Featured', 'Published At']);
            Post::with('category', 'tags')->chunk(50, function ($posts) use ($handle) {
                foreach ($posts as $post) {
                    fputcsv($handle, [
                        $post->title, $post->slug, $post->status,
                        $post->category?->name ?? '',
                        $post->tags->pluck('name')->implode('|'),
                        $post->excerpt ?? '', $post->content ?? '',
                        $post->visibility ?? 'public',
                        $post->featured ? 'Yes' : 'No',
                        $post->published_at?->toDateTimeString() ?? '',
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportJson(): StreamedResponse
    {
        $headers = ['Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="posts-' . now()->format('Y-m-d') . '.json"'];
        $callback = function () {
            $posts = Post::with('category', 'tags', 'author')->get()->map(fn($p) => [
                'title' => $p->title, 'slug' => $p->slug, 'status' => $p->status,
                'category' => $p->category?->name, 'tags' => $p->tags->pluck('name'),
                'excerpt' => $p->excerpt, 'content' => $p->content,
                'visibility' => $p->visibility, 'featured' => $p->is_featured,
                'published_at' => $p->published_at?->toIso8601String(),
                'author' => $p->author?->name,
            ]);
            print $posts->toJson(JSON_PRETTY_PRINT);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $title = trim($data['Title'] ?? '');
            if (empty($title)) continue;

            $cat = null;
            if (!empty($data['Category'])) {
                $cat = Category::where('name', $data['Category'])->orWhere('slug', Str::slug($data['Category']))->first();
            }

            $slug = Post::generateUniqueSlug($title);
            $tags = [];
            if (!empty($data['Tags'])) {
                foreach (explode('|', $data['Tags']) as $tName) {
                    $tName = trim($tName);
                    if ($tName) {
                        $tag = Tag::firstOrCreate(
                            ['slug' => Str::slug($tName)],
                            ['name' => $tName, 'created_by' => auth()->id()]
                        );
                        $tags[] = $tag->id;
                    }
                }
            }

            $post = Post::create([
                'title' => $title, 'slug' => $slug, 'status' => $data['Status'] ?? 'draft',
                'category_id' => $cat?->id, 'excerpt' => $data['Excerpt'] ?? null,
                'content' => $data['Content'] ?? '', 'visibility' => $data['Visibility'] ?? 'public',
                'is_featured' => ($data['Featured'] ?? 'No') === 'Yes',
                'published_at' => !empty($data['Published At']) ? $data['Published At'] : null,
                'author_id' => auth()->id(),
            ]);

            if (!empty($tags)) {
                $tagData = [];
                foreach ($tags as $tid) { $tagData[$tid] = ['relevance_score' => 0, 'created_at' => now()]; }
                $post->tags()->sync($tagData);
            }
            $count++;
        }
        fclose($handle);
        return redirect()->route('admin.posts.index')->with('success', "Imported {$count} posts.");
    }

    public function aiImprove(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string'],
            'action' => ['required', 'in:grammar,readability,expand,summarize,keywords'],
        ]);

        $prompts = [
            'grammar' => 'Fix grammar and spelling in this text. Return only the corrected text without explanation:',
            'readability' => 'Rewrite this to be more readable. Use shorter sentences and simpler words:',
            'expand' => 'Expand this content with more details and examples while keeping the same style:',
            'summarize' => 'Summarize this into 2-3 concise paragraphs:',
            'keywords' => 'Extract 5-10 SEO keywords from this content. Return as comma-separated list:',
        ];

        $result = $this->aiService->generateContent($prompts[$request->action] . "\n\n" . $request->content, 'article');

        return response()->json(['result' => $result]);
    }
}
