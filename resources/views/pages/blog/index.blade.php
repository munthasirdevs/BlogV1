<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Blog') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($posts as $post)
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        @if($post->featured_image)
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-48 w-full object-cover">
                        @endif
                        <div class="p-6">
                            @if($post->category)
                                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ $post->category->name }}</span>
                            @endif
                            <h3 class="mt-2 text-lg font-semibold text-gray-900">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-indigo-600">{{ $post->title }}</a>
                            </h3>
                            @if($post->excerpt)
                                <p class="mt-2 text-sm text-gray-600">{{ Str::limit($post->excerpt, 150) }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
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
                    <div class="col-span-full text-center text-sm text-gray-500">
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
