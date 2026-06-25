<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostRevisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:edit_posts');
    }

    public function index($postId): View
    {
        $post = Post::with('revisions.editor')->findOrFail($postId);
        $revisions = $post->revisions()
            ->with('editor')
            ->orderBy('revision_number', 'desc')
            ->get();

        return view('admin.posts.revisions', compact('post', 'revisions'));
    }

    public function show($postId, $revisionId): View
    {
        $post = Post::findOrFail($postId);
        $revision = PostRevision::with('editor')->findOrFail($revisionId);

        $currentContent = [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
        ];

        $revisionContent = [
            'title' => $revision->title_snapshot,
            'excerpt' => $revision->excerpt_snapshot,
            'content' => $revision->content_snapshot,
        ];

        $diffs = [];
        foreach ($currentContent as $field => $current) {
            $diffs[$field] = $this->computeDiff(
                $revisionContent[$field] ?? '',
                $current
            );
        }

        return view('admin.posts.revisions', compact('post', 'revision', 'revisions', 'diffs'));
    }

    public function restore($postId, $revisionId): RedirectResponse
    {
        $post = Post::findOrFail($postId);
        $revision = PostRevision::findOrFail($revisionId);

        $revisionNumber = PostRevision::where('post_id', $post->id)->max('revision_number') ?? 0;

        PostRevision::create([
            'post_id' => $post->id,
            'editor_id' => auth()->id(),
            'revision_number' => $revisionNumber + 1,
            'title_snapshot' => $post->title,
            'excerpt_snapshot' => $post->excerpt,
            'content_snapshot' => $post->content,
            'change_summary' => 'Restored from revision #' . $revision->revision_number,
        ]);

        $post->update([
            'title' => $revision->title_snapshot,
            'excerpt' => $revision->excerpt_snapshot,
            'content' => $revision->content_snapshot,
        ]);

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', 'Post restored to revision #' . $revision->revision_number . '.');
    }

    protected function computeDiff(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $oldMap = [];
        foreach ($oldLines as $i => $line) {
            $oldMap[$i] = $line;
        }

        $newMap = [];
        foreach ($newLines as $i => $line) {
            $newMap[$i] = $line;
        }

        $maxLen = max(count($oldLines), count($newLines));

        $diff = [];
        for ($i = 0; $i < $maxLen; $i++) {
            $oldLine = $oldLines[$i] ?? null;
            $newLine = $newLines[$i] ?? null;

            if ($oldLine === $newLine) {
                $diff[] = ['type' => 'unchanged', 'old' => $oldLine, 'new' => $newLine];
            } elseif ($oldLine === null) {
                $diff[] = ['type' => 'added', 'old' => null, 'new' => $newLine];
            } elseif ($newLine === null) {
                $diff[] = ['type' => 'removed', 'old' => $oldLine, 'new' => null];
            } else {
                $diff[] = ['type' => 'modified', 'old' => $oldLine, 'new' => $newLine];
            }
        }

        return $diff;
    }
}
