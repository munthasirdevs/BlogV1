@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex items-center gap-6 mb-8">
        <div class="w-20 h-20 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-white">{{ substr($author->name, 0, 1) }}</div>
        <div><h1 class="text-2xl font-bold">{{ $author->name }}</h1><p class="text-gray-600">{{ $author->bio ?? 'Author' }}</p></div>
    </div>
    <h2 class="text-xl font-semibold mb-4">Articles by {{ $author->name }}</h2>
    <div class="space-y-6">
        @foreach($posts as $post)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold"><a href="{{ route('blog.show', $post) }}" class="text-indigo-600 hover:text-indigo-800">{{ $post->title }}</a></h3>
            <p class="text-gray-600 mt-2">{{ Str::limit($post->excerpt, 150) }}</p>
            <div class="text-sm text-gray-500 mt-2">{{ $post->published_at->format('M d, Y') }} · {{ $post->reading_time }} min read</div>
        </div>
        @endforeach
    </div>
    {{ $posts->links() }}
</div>
@endsection
