<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_comments');
    }

    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');

        $query = Comment::with('post', 'user', 'approver');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.comments.index', compact('comments', 'status'));
    }

    public function approve($id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

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
}
