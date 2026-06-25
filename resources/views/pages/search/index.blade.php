<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            {{-- Search Form --}}
            <div class="mb-8 overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <form action="{{ route('search') }}" method="GET">
                        <div class="flex gap-3">
                            <input
                                type="text"
                                name="q"
                                value="{{ $keyword }}"
                                placeholder="{{ __('Search posts...') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($keyword)
                {{-- Results Count --}}
                <div class="mb-6">
                    <p class="text-sm text-gray-600">
                        {{ __('Found') }} <span class="font-semibold">{{ $resultsCount }}</span> {{ __('result(s) for') }} "<span class="font-semibold">{{ $keyword }}</span>"
                    </p>
                </div>

                {{-- Results --}}
                @if($results->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($results as $post)
                            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                                <div class="p-6">
                                    @if($post->category)
                                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ $post->category->name }}</span>
                                    @endif
                                    <h3 class="mt-1 text-lg font-semibold text-gray-900">
                                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-indigo-600">{{ $post->title }}</a>
                                    </h3>
                                    @if($post->excerpt)
                                        <p class="mt-2 text-sm text-gray-600">{{ Str::limit($post->excerpt, 200) }}</p>
                                    @endif
                                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
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
                    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="p-12 text-center">
                            <p class="text-gray-500">{{ __('No posts found matching your search.') }}</p>
                            <p class="mt-2 text-sm text-gray-400">{{ __('Try different keywords or browse categories.') }}</p>
                        </div>
                    </div>
                @endif
            @else
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-12 text-center">
                        <p class="text-gray-500">{{ __('Enter a keyword to search posts.') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
