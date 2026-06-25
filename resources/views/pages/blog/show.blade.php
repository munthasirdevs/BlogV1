<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $post->title }}
                </h2>
                <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                    <span>{{ __('By') }} {{ $post->author?->name ?? '—' }}</span>
                    <span>&middot;</span>
                    <span>{{ $post->published_at?->format('M d, Y') }}</span>
                    <span>&middot;</span>
                    <span>{{ $post->reading_time }} {{ __('min read') }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <article class="overflow-hidden rounded-lg bg-white shadow">
                @if($post->featured_image)
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full object-cover">
                @endif

                <div class="p-8">
                    @if($post->category || $post->tags->isNotEmpty())
                        <div class="mb-6 flex flex-wrap items-center gap-2">
                            @if($post->category)
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $post->category->name }}</span>
                            @endif
                            @foreach($post->tags as $tag)
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($post->excerpt)
                        <p class="mb-6 text-lg italic text-gray-600">{{ $post->excerpt }}</p>
                    @endif

                    <div class="prose prose-lg max-w-none">
                        {!! $post->content !!}
                    </div>
                </div>
            </article>

            @if($relatedPosts->isNotEmpty())
                <div class="mt-12">
                    <h3 class="mb-6 text-xl font-semibold text-gray-900">{{ __('Related Posts') }}</h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        @foreach($relatedPosts as $related)
                            <div class="overflow-hidden rounded-lg bg-white shadow">
                                @if($related->featured_image)
                                    <img src="{{ $related->featured_image }}" alt="{{ $related->title }}" class="h-40 w-full object-cover">
                                @endif
                                <div class="p-4">
                                    <h4 class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('blog.show', $related->slug) }}" class="hover:text-indigo-600">{{ $related->title }}</a>
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-500">{{ $related->published_at?->format('M d, Y') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @include('partials.comments', ['post' => $post])
        </div>
    </div>
</x-app-layout>
