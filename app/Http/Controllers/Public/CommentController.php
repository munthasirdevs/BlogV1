<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'guest_name' => ['nullable', 'required_without:user_id', 'string', 'max:255'],
            'guest_email' => ['nullable', 'required_without:user_id', 'email', 'max:255'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        try {
            $this->commentService->submit($validated, $request->ip());
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Your comment has been submitted and is pending review.');
    }
}
