<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            {{-- Search Form --}}
            <div class="mb-8 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="p-6">
                    <form action="{{ route('search') }}" method="GET">
                        <div class="flex gap-3">
                            <input
                                type="text"
                                name="q"
                                value="{{ $keyword }}"
                                placeholder="{{ __('Search posts...') }}"
                                class="block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border"
                                style="border-color: var(--color-border)"
                            >
                            <button type="submit" class="inline-flex items-center rounded-md px-6 py-2 text-sm font-semibold text-white shadow-sm" style="background-color: var(--color-primary-600)">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($keyword)
                {{-- Results Count --}}
                <div class="mb-6">
                    <p class="text-sm" style="color: var(--color-text-body)">
                        {{ __('Found') }} <span class="font-semibold">{{ $resultsCount }}</span> {{ __('result(s) for') }} "<span class="font-semibold">{{ $keyword }}</span>"
                    </p>
                </div>

                {{-- Results --}}
                @if($results->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($results as $post)
                            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                                <div class="p-6">
                                    @if($post->category)
                                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-primary-600)">{{ $post->category->name }}</span>
                                    @endif
                                    <h3 class="mt-1 text-lg font-semibold" style="color: var(--color-text-heading)">
                                        <a href="{{ route('blog.show', $post->slug) }}" style="color: var(--color-primary-600)">{{ $post->title }}</a>
                                    </h3>
                                    @if($post->excerpt)
                                        <p class="mt-2 text-sm" style="color: var(--color-text-body)">{{ Str::limit($post->excerpt, 200) }}</p>
                                    @endif
                                    <div class="mt-3 flex items-center gap-4 text-xs" style="color: var(--color-text-muted)">
                                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $post->reading_time }} {{ __('min read') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $results->appends(['q' => $keyword])->links() }}
                    </div>
                @else
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="p-12 text-center">
                            <p style="color: var(--color-text-muted)">{{ __('No posts found matching your search.') }}</p>
                            <p class="mt-2 text-sm" style="color: var(--color-text-muted)">{{ __('Try different keywords or browse categories.') }}</p>
                        </div>
                    </div>
                @endif
            @else
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-12 text-center">
                        <p style="color: var(--color-text-muted)">{{ __('Enter a keyword to search posts.') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
