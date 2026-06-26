<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">{{ __('Admin Dashboard') }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.analytics') }}" class="text-sm" style="color: var(--color-primary-600)">{{ __('Full Analytics') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            {{-- Stats Grid --}}
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <div class="rounded-lg shadow-sm p-4" style="background-color: var(--color-surface-card)">
                    <div class="text-xs font-medium" style="color: var(--color-text-muted)">{{ __('Posts') }}</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalPosts'] }}</div>
                    <div class="mt-1 flex gap-2 text-[10px]" style="color: var(--color-text-muted)">
                        <span style="color: var(--color-success)">{{ $stats['publishedPosts'] }} pub</span>
                        <span class="text-yellow-600">{{ $stats['draftPosts'] }} drf</span>
                        <span class="text-blue-600">{{ $stats['scheduledPosts'] }} sch</span>
                    </div>
                </div>
                <div class="rounded-lg shadow-sm p-4" style="background-color: var(--color-surface-card)">
                    <div class="text-xs font-medium" style="color: var(--color-text-muted)">{{ __('Workflow') }}</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $stats['postsInReview'] }}</div>
                    <div class="mt-1 text-[10px]" style="color: var(--color-text-muted)">
                        <span class="text-amber-600">{{ $stats['postsInReview'] }} in review</span>
                        <span style="color: var(--color-success)"> · {{ $stats['approvedPosts'] }} approved</span>
                    </div>
                </div>
                <div class="rounded-lg shadow-sm p-4" style="background-color: var(--color-surface-card)">
                    <div class="text-xs font-medium" style="color: var(--color-text-muted)">{{ __('SEO Score') }}</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ round($stats['avgSeoScore']) }}</div>
                    <div class="mt-1 text-[10px]" style="color: var(--color-text-muted)">avg across published</div>
                </div>
                <div class="rounded-lg shadow-sm p-4" style="background-color: var(--color-surface-card)">
                    <div class="text-xs font-medium" style="color: var(--color-text-muted)">{{ __('Queue') }}</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $stats['pendingJobs'] }}</div>
                    <div class="mt-1 text-[10px]" style="color: var(--color-text-muted)"><span class="text-red-600">{{ $stats['failedJobs'] }} failed</span></div>
                </div>
                <div class="rounded-lg shadow-sm p-4" style="background-color: var(--color-surface-card)">
                    <div class="text-xs font-medium" style="color: var(--color-text-muted)">{{ __('Revisions') }}</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-heading)">{{ $stats['totalRevisions'] }}</div>
                    <div class="mt-1 text-[10px]" style="color: var(--color-text-muted)"><span class="text-indigo-600">{{ $stats['aiRevisions'] }} AI</span></div>
                </div>
            </div>

            {{-- Two column layout --}}
            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Recent Posts --}}
                <div class="lg:col-span-2 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="border-b px-6 py-4 flex items-center justify-between" style="border-color: var(--color-border)">
                        <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Recent Published') }}</h3>
                        <a href="{{ route('admin.posts.index') }}" class="text-xs" style="color: var(--color-primary-600)">{{ __('View all') }}</a>
                    </div>
                    <div class="p-6">
                        @if($recentPosts->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($recentPosts as $post)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="text-sm font-medium hover:underline truncate block" style="color: var(--color-text-heading)">{{ $post->title }}</a>
                                    <p class="text-xs" style="color: var(--color-text-muted)">{{ $post->author?->name }} · {{ $post->published_at?->format('M d, Y') }} · {{ $post->views_count }} views</p>
                                </div>
                                @if($post->category)
                                <span class="text-xs px-2 py-0.5 rounded-full ml-2" style="background-color: var(--color-surface-elevated); color: var(--color-text-muted)">{{ $post->category->name }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No published posts yet.') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-6">

                    {{-- Pending Comments --}}
                    @if($pendingQueue->isNotEmpty())
                    <div class="rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b px-4 py-3 flex items-center justify-between" style="border-color: var(--color-border)">
                            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">{{ __('Pending Comments') }}</h3>
                            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="text-xs" style="color: var(--color-primary-600)">{{ $stats['pendingComments'] }}</a>
                        </div>
                        <div class="p-3 space-y-2">
                            @foreach($pendingQueue as $comment)
                            <div class="text-xs" style="color: var(--color-text-body)">
                                <span class="font-medium">{{ $comment->getAuthorName() }}</span> on <a href="{{ route('admin.posts.edit', $comment->post_id) }}" class="hover:underline" style="color: var(--color-primary-600)">{{ Str::limit($comment->post?->title ?? '', 30) }}</a>
                                <p class="truncate mt-0.5" style="color: var(--color-text-muted)">{{ Str::limit($comment->body, 60) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Failed Jobs --}}
                    @if($failedJobsDetail->isNotEmpty())
                    <div class="rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b px-4 py-3" style="border-color: var(--color-border)">
                            <h3 class="text-sm font-semibold" style="color: var(--color-text-heading)">{{ __('Failed Jobs') }}
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" style="background-color: var(--color-error-bg); color: var(--color-error)">{{ $stats['failedJobs'] }}</span>
                            </h3>
                        </div>
                        <div class="p-3 space-y-2">
                            @foreach($failedJobsDetail as $job)
                            <div class="text-xs" style="color: var(--color-text-body)">
                                <span class="font-medium">{{ $job->job_type }}</span>
                                <span class="text-red-600"> failed</span>
                                <p class="truncate mt-0.5" style="color: var(--color-text-muted)">{{ Str::limit($job->error_message ?? '', 80) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <a href="{{ route('admin.posts.create') }}" class="rounded-lg px-4 py-3 text-sm font-semibold text-white text-center" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">{{ __('New Post') }}</a>
                <a href="{{ route('admin.categories.create') }}" class="rounded-lg px-4 py-3 text-sm font-semibold text-white text-center" style="background-color: var(--color-success);">{{ __('New Category') }}</a>
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="rounded-lg px-4 py-3 text-sm font-semibold text-white text-center" style="background-color: var(--color-warning);">{{ __('Moderate') }}</a>
                <a href="{{ route('admin.ai.index') }}" class="rounded-lg px-4 py-3 text-sm font-semibold text-white text-center" style="background-color: var(--color-info);">{{ __('AI Tools') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
