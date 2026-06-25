@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex items-center gap-6 mb-8">
        <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold text-white" style="background-color: var(--color-surface-elevated)">{{ substr($author->name, 0, 1) }}</div>
        <div><h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $author->name }}</h1><p style="color: var(--color-text-body)">{{ $author->bio ?? 'Author' }}</p></div>
    </div>
    <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text-heading)">Articles by {{ $author->name }}</h2>
    <div class="space-y-6">
        @foreach($posts as $post)
        <div class="rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
            <h3 class="text-lg font-semibold"><a href="{{ route('blog.show', $post) }}" style="color: var(--color-primary-600)">{{ $post->title }}</a></h3>
            <p class="mt-2" style="color: var(--color-text-body)">{{ Str::limit($post->excerpt, 150) }}</p>
            <div class="text-sm mt-2" style="color: var(--color-text-muted)">{{ $post->published_at->format('M d, Y') }} · {{ $post->reading_time }} min read</div>
        </div>
        @endforeach
    </div>
    {{ $posts->links() }}
</div>
@endsection
