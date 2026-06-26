<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Create Post') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    <form action="{{ route('admin.posts.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Title') }}</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                            @error('title') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Slug') }}</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                            @error('slug') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="category_id" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Category') }}</label>
                            <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="">{{ __('None') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tags" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Tags') }}</label>
                            <select name="tags[]" id="tags" multiple class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                            @error('tags') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="content" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Content') }}</label>
                            <x-rich-editor name="content" value="{{ old('content') }}" placeholder="Start writing your post..." />
                            @error('content') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="excerpt" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Excerpt') }}</label>
                            <textarea name="excerpt" id="excerpt" rows="3" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('excerpt') }}</textarea>
                            @error('excerpt') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="featured_image" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Featured Image URL') }}</label>
                            <input type="text" name="featured_image" id="featured_image" value="{{ old('featured_image') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                            @error('featured_image') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Status') }}</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>{{ __('Scheduled') }}</option>
                                <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="visibility" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Visibility') }}</label>
                            <select name="visibility" id="visibility" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>{{ __('Public') }}</option>
                                <option value="private" {{ old('visibility') == 'private' ? 'selected' : '' }}>{{ __('Private') }}</option>
                                <option value="unlisted" {{ old('visibility') == 'unlisted' ? 'selected' : '' }}>{{ __('Unlisted') }}</option>
                            </select>
                            @error('visibility') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="rounded shadow-sm focus:ring-indigo-500" style="border-color: var(--color-border); color: var(--color-primary-600)">
                                <span class="ml-2 text-sm" style="color: var(--color-text-body)">{{ __('Featured') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                {{ __('Create') }}
                            </button>
                            <a href="{{ route('admin.posts.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
