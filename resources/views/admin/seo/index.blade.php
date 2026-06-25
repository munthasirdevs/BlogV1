<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('SEO Dashboard') }}
            </h2>
            <form action="{{ route('admin.seo.sitemap.generate') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                    {{ __('Regenerate Sitemap') }}
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Average SEO Score') }}</div>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-3xl font-bold {{ $stats['average_seo_score'] >= 70 ? 'text-green-600' : ($stats['average_seo_score'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $stats['average_seo_score'] }}
                            </span>
                            <span class="ml-1 text-sm text-gray-500">/ 100</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Total Posts') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_posts'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Indexed Posts') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['indexed_posts'] }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Sitemap') }}</div>
                        <div class="mt-2">
                            @if($stats['sitemap_exists'])
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    {{ __('Generated') }}
                                </span>
                                <a href="{{ $stats['sitemap_url'] }}" target="_blank" class="ml-2 text-xs text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                            @else
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                    {{ __('Not Generated') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Posts Needing SEO Improvement (Score < 50)') }}</h3>
                </div>
                <div class="p-6">
                    @if($stats['low_score_posts']->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Title') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Author') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('SEO Score') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($stats['low_score_posts'] as $post)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $post->title }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-500">{{ $post->author?->name ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                {{ $post->seo_score ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                            <a href="{{ route('admin.posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                            <button type="button" class="ml-2 text-yellow-600 hover:text-yellow-900 analyze-btn" data-post-id="{{ $post->id }}">
                                                {{ __('Analyze') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-500">{{ __('All published posts have good SEO scores.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.analyze-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const postId = this.dataset.postId;
                    const row = this.closest('tr');

                    fetch('{{ url('admin/seo/analyze') }}/' + postId)
                        .then(res => res.json())
                        .then(data => {
                            alert('SEO Analysis Results:\n\n' +
                                'Title: ' + data.title_score + '/100\n' +
                                'Description: ' + data.description_score + '/100\n' +
                                'Headings: ' + data.headings_score + '/100\n' +
                                'Content: ' + data.content_score + '/100\n' +
                                'Images: ' + data.images_score + '/100\n' +
                                'Links: ' + data.links_score + '/100\n' +
                                'Overall: ' + data.overall_score + '/100\n\n' +
                                'Recommendations:\n' + data.recommendations.join('\n'));
                        });
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
