<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">#{{ $tag->name }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tags.edit', $tag) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">{{ __('Edit Tag') }}</a>
                <a href="{{ route('admin.tags.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">{{ __('All Tags') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            @if($tag->color)<span class="w-4 h-4 rounded-full" style="background-color: {{ $tag->color }}"></span>@endif
                            <h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">#{{ $tag->name }}</h1>
                        </div>
                        @if($tag->description)<p class="mb-4" style="color: var(--color-text-body)">{{ $tag->description }}</p>@endif
                        @if($recentPosts->isNotEmpty())
                        <h3 class="text-lg font-semibold mb-3" style="color: var(--color-text-heading)">{{ __('Recent Posts') }}</h3>
                        <div class="space-y-2">
                            @foreach($recentPosts as $rp)
                            <a href="{{ route('admin.posts.edit', $rp) }}" class="block text-sm hover:underline" style="color: var(--color-primary-600)">{{ $rp->title }}</a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="p-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Status</span><span>{{ ucfirst($tag->status) }}</span></div>
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Posts</span><span>{{ $postCount }}</span></div>
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Slug</span><span>{{ $tag->slug }}</span></div>
                            @if($tag->trending_score > 0)<div class="flex justify-between"><span style="color: var(--color-text-muted)">Trending</span><span>{{ $tag->trending_score }}</span></div>@endif
                            <div class="flex justify-between"><span style="color: var(--color-text-muted)">Created</span><span>{{ $tag->created_at->format('M d, Y') }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
