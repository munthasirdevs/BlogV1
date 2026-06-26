@php
    $approvedComments = $post->comments()->with('user', 'replies')->approved()->whereNull('parent_id')->orderBy('created_at', 'desc')->get();
@endphp

<section class="mt-12">
    <h3 class="mb-6 text-xl font-semibold" style="color: var(--color-text-heading)">{{ __('Comments') }} ({{ $approvedComments->count() }})</h3>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($approvedComments->isNotEmpty())
        <div class="space-y-6">
            @foreach($approvedComments as $comment)
                <div class="rounded-lg border p-4" style="border-color: var(--color-border); background-color: var(--color-surface-card)">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-medium" style="color: var(--color-text-heading)">{{ $comment->user?->name ?? $comment->guest_name ?? __('Anonymous') }}</span>
                            <span class="ml-2 text-sm" style="color: var(--color-text-muted)">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <p class="mt-2" style="color: var(--color-text-body)">{{ $comment->body }}</p>

                    @if($comment->replies->isNotEmpty())
                        <div class="ml-6 mt-4 space-y-4 border-l-2 pl-4" style="border-color: var(--color-border)">
                            @foreach($comment->replies as $reply)
                                <div>
                                    <div class="flex items-start justify-between">
                                        <span class="font-medium" style="color: var(--color-text-heading)">{{ $reply->user?->name ?? $reply->guest_name ?? __('Anonymous') }}</span>
                                        <span class="text-sm" style="color: var(--color-text-muted)">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1" style="color: var(--color-text-body)">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No comments yet. Be the first to comment!') }}</p>
    @endif

    <div class="mt-8 rounded-lg p-6 shadow" style="background-color: var(--color-surface-card)">
        <h4 class="mb-4 text-lg font-semibold" style="color: var(--color-text-heading)">{{ __('Leave a Comment') }}</h4>

        <form action="{{ route('public.comments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}">

            <div class="mb-4">
                <label for="body" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Comment') }}</label>
                <textarea name="body" id="body" rows="4" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border" style="border-color: var(--color-border)" required>{{ old('body') }}</textarea>
                @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @guest
                <div class="mb-4">
                    <label for="guest_name" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Name') }}</label>
                    <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border" style="border-color: var(--color-border)" required>
                    @error('guest_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label for="guest_email" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Email') }}</label>
                    <input type="email" name="guest_email" id="guest_email" value="{{ old('guest_email') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border" style="border-color: var(--color-border)" required>
                    @error('guest_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endguest

            <div class="flex items-center justify-end">
                <button type="submit" class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white" style="background-color: var(--color-primary-600)">
                    {{ __('Submit Comment') }}
                </button>
            </div>
        </form>
    </div>
</section>
