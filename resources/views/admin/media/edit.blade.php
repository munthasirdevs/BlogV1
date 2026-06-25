<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Edit Media') }}
            </h2>
            <span class="text-sm" style="color: var(--color-text-muted)">
                {{ $medium->original_name }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        @if(str_starts_with($medium->mime_type, 'image/'))
                            <div class="overflow-hidden rounded-lg bg-gray-100">
                                <img src="{{ $medium->file_url }}" alt="{{ $medium->alt_text ?? $medium->original_name }}" class="w-full">
                            </div>
                        @elseif(str_starts_with($medium->mime_type, 'video/'))
                            <div class="overflow-hidden rounded-lg bg-gray-100">
                                <video controls class="w-full">
                                    <source src="{{ $medium->file_url }}" type="{{ $medium->mime_type }}">
                                </video>
                            </div>
                        @else
                            <div class="flex items-center justify-center rounded-lg bg-gray-100 py-20">
                                <div class="text-center">
                                    <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <p class="mt-2 text-sm" style="color: var(--color-text-muted)">{{ $medium->mime_type }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('Filename') }}</span>
                                <span class="font-medium" style="color: var(--color-text-heading)">{{ $medium->original_name }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('File size') }}</span>
                                <span class="font-medium" style="color: var(--color-text-heading)">{{ number_format($medium->file_size / 1024, 1) }} KB</span>
                            </div>
                            @if($medium->width && $medium->height)
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('Dimensions') }}</span>
                                <span class="font-medium" style="color: var(--color-text-heading)">{{ $medium->width }} &times; {{ $medium->height }} px</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('MIME Type') }}</span>
                                <span class="font-medium" style="color: var(--color-text-heading)">{{ $medium->mime_type }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('Uploaded') }}</span>
                                <span class="font-medium" style="color: var(--color-text-heading)">{{ $medium->created_at->format('M d, Y g:i A') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="" style="color: var(--color-text-muted)">{{ __('Optimization') }}</span>
                                <span class="font-medium">
                                    @if($medium->optimization_status === 'completed')
                                        <span class="" style="color: var(--color-success)">{{ __('Completed') }}</span>
                                    @elseif($medium->optimization_status === 'failed')
                                        <span class="" style="color: var(--color-error)">{{ __('Failed') }}</span>
                                    @else
                                        <span class="" style="color: var(--color-warning)">{{ __('Pending') }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <form action="{{ route('admin.media.update', $medium) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Title') }}</label>
                                <input type="text" name="title" id="title" value="{{ old('title', $medium->title) }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                @error('title') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="alt_text" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Alt Text') }}</label>
                                <input type="text" name="alt_text" id="alt_text" value="{{ old('alt_text', $medium->alt_text) }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                @error('alt_text') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="caption" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Caption') }}</label>
                                <textarea name="caption" id="caption" rows="2" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('caption', $medium->caption) }}</textarea>
                                @error('caption') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Description') }}</label>
                                <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('description', $medium->description) }}</textarea>
                                @error('description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="folder_id" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Folder') }}</label>
                                <select name="folder_id" id="folder_id" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                    <option value="">{{ __('Root') }}</option>
                                    @foreach($folders as $folder)
                                        <option value="{{ $folder->id }}" {{ old('folder_id', $medium->folder_id) == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                    @endforeach
                                </select>
                                @error('folder_id') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $medium->is_featured) ? 'checked' : '' }} class="rounded shadow-sm focus:ring-indigo-500" style="border-color: var(--color-border); color: var(--color-primary-600)">
                                    <span class="ml-2 text-sm" style="color: var(--color-text-body)">{{ __('Featured') }}</span>
                                </label>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                        {{ __('Update') }}
                                    </button>
                                    <a href="{{ route('admin.media.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                        {{ __('Cancel') }}
                                    </a>
                                </div>
                                <form action="{{ route('admin.media.destroy', $medium) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500" onclick="return confirm('{{ __('Are you sure you want to delete this file?') }}')">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
