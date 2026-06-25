@php
    $approvedComments = $post->comments()->with('user', 'replies')->approved()->whereNull('parent_id')->orderBy('created_at', 'desc')->get();
@endphp

<section class="mt-12">
    <h3 class="mb-6 text-xl font-semibold text-gray-900">{{ __('Comments') }} ({{ $post->comments()->approved()->count() }})</h3>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($approvedComments->isNotEmpty())
        <div class="space-y-6">
            @foreach($approvedComments as $comment)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-medium text-gray-900">{{ $comment->user?->name ?? $comment->guest_name ?? __('Anonymous') }}</span>
                            <span class="ml-2 text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <p class="mt-2 text-gray-700">{{ $comment->body }}</p>

                    @if($comment->replies->isNotEmpty())
                        <div class="ml-6 mt-4 space-y-4 border-l-2 border-gray-100 pl-4">
                            @foreach($comment->replies as $reply)
                                <div>
                                    <div class="flex items-start justify-between">
                                        <span class="font-medium text-gray-900">{{ $reply->user?->name ?? $reply->guest_name ?? __('Anonymous') }}</span>
                                        <span class="text-sm text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1 text-gray-700">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500">{{ __('No comments yet. Be the first to comment!') }}</p>
    @endif

    <div class="mt-8 rounded-lg bg-white p-6 shadow">
        <h4 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Leave a Comment') }}</h4>

        <form action="{{ route('public.comments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}">

            <div class="mb-4">
                <label for="body" class="block text-sm font-medium text-gray-700">{{ __('Comment') }}</label>
                <textarea name="body" id="body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('body') }}</textarea>
                @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @guest
                <div class="mb-4">
                    <label for="guest_name" class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @error('guest_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label for="guest_email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                    <input type="email" name="guest_email" id="guest_email" value="{{ old('guest_email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @error('guest_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endguest

            <div class="flex items-center justify-end">
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                    {{ __('Submit Comment') }}
                </button>
            </div>
        </form>
    </div>
</section>
