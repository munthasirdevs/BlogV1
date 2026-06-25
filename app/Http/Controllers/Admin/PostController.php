<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:edit_posts');
    }

    public function index(): View
    {
        $posts = Post::with('author', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.posts.index', compact('posts'));
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
            'slug' => ['required', 'string', 'max:255', 'unique:posts,slug'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'content_format' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'visibility' => ['nullable', 'in:public,private,password'],
            'is_featured' => ['nullable', 'boolean'],
            'is_scheduled' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

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

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', 'Post created successfully.');
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
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'visibility' => ['nullable', 'in:public,private,password'],
            'is_featured' => ['nullable', 'boolean'],
            'is_scheduled' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $post->update($validated);

        if (isset($validated['tags'])) {
            $tagData = [];
            foreach ($validated['tags'] as $tagId) {
                $tagData[$tagId] = ['relevance_score' => 0, 'created_at' => now()];
            }
            $post->tags()->sync($tagData);
        } else {
            $post->tags()->sync([]);
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

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }
}
