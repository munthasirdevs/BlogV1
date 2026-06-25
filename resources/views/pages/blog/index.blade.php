<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Blog') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($posts as $post)
                    <div class="overflow-hidden rounded-lg shadow" style="background-color: var(--color-surface-card)">
                        @if($post->featured_image)
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-48 w-full object-cover">
                        @endif
                        <div class="p-6">
                            @if($post->category)
                                <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-primary-600)">{{ $post->category->name }}</span>
                            @endif
                            <h3 class="mt-2 text-lg font-semibold" style="color: var(--color-text-heading)">
                                <a href="{{ route('blog.show', $post->slug) }}" style="color: var(--color-primary-600)">{{ $post->title }}</a>
                            </h3>
                            @if($post->excerpt)
                                <p class="mt-2 text-sm" style="color: var(--color-text-body)">{{ Str::limit($post->excerpt, 150) }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between text-xs" style="color: var(--color-text-muted)">
                                <span>{{ $post->author?->name ?? '—' }}</span>
                                <div class="flex items-center gap-2">
                                    <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $post->reading_time }} {{ __('min read') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-sm" style="color: var(--color-text-muted)">
                        {{ __('No posts found.') }}
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
