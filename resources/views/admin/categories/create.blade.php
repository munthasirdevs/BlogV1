<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Create Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    <form action="{{ route('admin.categories.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                            @error('name') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Slug') }}</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                            @error('slug') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="parent_id" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Parent Category') }}</label>
                            <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="">{{ __('None') }}</option>
                                @foreach($parentCategories as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                            @error('parent_id') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="short_description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Short Description') }}</label>
                            <textarea name="short_description" id="short_description" rows="3" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('short_description') }}</textarea>
                            @error('short_description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Status') }}</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                                <option value="hidden" {{ old('status') == 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="sort_order" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Sort Order') }}</label>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                            @error('sort_order') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="featured" value="1" {{ old('featured') ? 'checked' : '' }} class="rounded shadow-sm focus:ring-indigo-500" style="border-color: var(--color-border); color: var(--color-primary-600)">
                                <span class="ml-2 text-sm" style="color: var(--color-text-body)">{{ __('Featured') }}</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label for="color" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Color') }}</label>
                            <input type="text" name="color" id="color" value="{{ old('color') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                            @error('color') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                {{ __('Create') }}
                            </button>
                            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
