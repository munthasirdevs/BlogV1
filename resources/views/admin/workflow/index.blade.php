@extends('layouts.admin')
@section('title', 'Content Workflow')
@section('content')
<h1 class="text-2xl font-bold mb-6" style="color: var(--color-text-heading)">Content Workflow</h1>
<div class="grid gap-6 lg:grid-cols-4">
    {{-- Drafts --}}
    <div class="rounded-lg shadow" style="background-color: var(--color-surface-card);">
        <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--color-border)">
            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">Drafts</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $drafts->total() }}</span>
        </div>
        <div class="p-3 space-y-3 min-h-[200px]">
            @forelse($drafts as $post)
            <a href="{{ route('admin.posts.edit', $post) }}" class="block p-3 rounded-lg border text-sm hover:shadow-sm transition" style="border-color: var(--color-border); background-color: var(--color-surface)">
                <div class="font-medium truncate" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $post->author?->name }} · {{ $post->updated_at->diffForHumans() }}</div>
            </a>
            @empty
            <p class="text-sm text-center py-8" style="color: var(--color-text-muted)">No drafts</p>
            @endforelse
        </div>
    </div>

    {{-- In Review --}}
    <div class="rounded-lg shadow" style="background-color: var(--color-surface-card);">
        <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--color-border)">
            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">In Review</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $inReview->total() }}</span>
        </div>
        <div class="p-3 space-y-3 min-h-[200px]">
            @forelse($inReview as $post)
            <div class="p-3 rounded-lg border text-sm" style="border-color: var(--color-border); background-color: var(--color-surface)">
                <div class="font-medium truncate" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $post->author?->name }} · {{ __('Status') }}: {{ str_replace('_', ' ', $post->status) }}</div>
                <div class="mt-2 flex gap-1">
                    <form action="{{ route('admin.posts.approve', $post) }}" method="POST" class="inline">@csrf<button type="submit" class="text-xs px-2 py-1 rounded" style="background-color: var(--color-success-bg); color: var(--color-success)">Approve</button></form>
                    <form action="{{ route('admin.posts.reject', $post) }}" method="POST" class="inline">@csrf<input type="hidden" name="reason" value="Needs revision"><button type="submit" class="text-xs px-2 py-1 rounded" style="background-color: var(--color-error-bg); color: var(--color-error)">Reject</button></form>
                </div>
            </div>
            @empty
            <p class="text-sm text-center py-8" style="color: var(--color-text-muted)">Nothing in review</p>
            @endforelse
        </div>
    </div>

    {{-- Approved --}}
    <div class="rounded-lg shadow" style="background-color: var(--color-surface-card);">
        <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--color-border)">
            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">Approved</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ $approved->total() }}</span>
        </div>
        <div class="p-3 space-y-3 min-h-[200px]">
            @forelse($approved as $post)
            <a href="{{ route('admin.posts.edit', $post) }}" class="block p-3 rounded-lg border text-sm hover:shadow-sm transition" style="border-color: var(--color-border); background-color: var(--color-surface)">
                <div class="font-medium truncate" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $post->author?->name }} · {{ __('Ready to publish') }}</div>
            </a>
            @empty
            <p class="text-sm text-center py-8" style="color: var(--color-text-muted)">No approved posts</p>
            @endforelse
        </div>
    </div>

    {{-- Scheduled --}}
    <div class="rounded-lg shadow" style="background-color: var(--color-surface-card);">
        <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--color-border)">
            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">Scheduled</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $scheduled->total() }}</span>
        </div>
        <div class="p-3 space-y-3 min-h-[200px]">
            @forelse($scheduled as $post)
            <a href="{{ route('admin.posts.edit', $post) }}" class="block p-3 rounded-lg border text-sm hover:shadow-sm transition" style="border-color: var(--color-border); background-color: var(--color-surface)">
                <div class="font-medium truncate" style="color: var(--color-text-heading)">{{ $post->title }}</div>
                <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ __('Scheduled') }}: {{ $post->scheduled_at?->format('M d, Y g:i A') ?? '—' }}</div>
            </a>
            @empty
            <p class="text-sm text-center py-8" style="color: var(--color-text-muted)">Nothing scheduled</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
