<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected array $spamKeywords = [
        'buy now', 'click here', 'free money', 'earn money fast',
        'viagra', 'casino', 'lottery', 'prize', 'discount',
        'http://', 'https://', 'www.',
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'guest_name' => ['nullable', 'required_without:user_id', 'string', 'max:255'],
            'guest_email' => ['nullable', 'required_without:user_id', 'email', 'max:255'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $post = Post::published()->findOrFail($validated['post_id']);

        $isSpam = $this->checkForSpam($validated['body']);

        $comment = new Comment();
        $comment->post_id = $post->id;
        $comment->parent_id = $validated['parent_id'] ?? null;
        $comment->body = $validated['body'];
        $comment->status = $isSpam ? 'spam' : 'pending';
        $comment->ip_address = $request->ip();

        if (auth()->check()) {
            $comment->user_id = auth()->id();
        } else {
            $comment->guest_name = $validated['guest_name'];
            $comment->guest_email = $validated['guest_email'];
        }

        $comment->save();

        $message = $isSpam
            ? 'Your comment has been submitted and is being reviewed.'
            : 'Your comment has been submitted and is pending approval.';

        return redirect()->back()->with('success', $message);
    }

    protected function checkForSpam(string $body): bool
    {
        $bodyLower = mb_strtolower($body);

        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($bodyLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
