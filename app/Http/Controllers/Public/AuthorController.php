<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(string $name): View
    {
        $author = User::role('author')
            ->where('name', $name)
            ->firstOrFail();

        $posts = Post::published()
            ->where('author_id', $author->id)
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('pages.author.show', compact('author', 'posts'));
    }
}
