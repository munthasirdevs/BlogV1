<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService
    ) {
        $this->middleware('permission:manage_comments');
    }

    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = Comment::with('post', 'user', 'approver');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%");
            });
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.comments.index', compact('comments', 'status', 'search'));
    }

    public function approve($id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return redirect()->back()->with('success', 'Comment approved.');
    }

    public function reject($id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'trash']);
        return redirect()->back()->with('success', 'Comment rejected.');
    }

    public function markSpam($id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'spam']);
        return redirect()->back()->with('success', 'Comment marked as spam.');
    }

    public function destroy($id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();
        return redirect()->back()->with('success', 'Comment deleted.');
    }

    public function react(Request $request, Comment $comment): JsonResponse
    {
        $request->validate(['type' => ['required', 'string', 'in:like,love,laugh,insightful,support']]);

        $existing = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->where('reaction_type', $request->type)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'action' => 'removed']);
        }

        CommentReaction::create([
            'comment_id' => $comment->id,
            'user_id' => auth()->id(),
            'reaction_type' => $request->type,
        ]);

        return response()->json(['success' => true, 'action' => 'added']);
    }
}
