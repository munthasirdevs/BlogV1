<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Create Tag') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--color-surface-card)">
                <div class="p-6" style="color: var(--color-text-heading)">
                    <form action="{{ route('admin.tags.store') }}" method="POST">
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
                            <label for="description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Description') }}</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('description') }}</textarea>
                            @error('description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="color" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Color') }}</label>
                            <input type="text" name="color" id="color" value="{{ old('color') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" placeholder="#ffffff">
                            @error('color') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Status') }}</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <hr class="my-6">

                        <div class="mb-4">
                            <label for="seo_title" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('SEO Title') }}</label>
                            <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title') }}" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                            @error('seo_title') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="seo_description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('SEO Description') }}</label>
                            <textarea name="seo_description" id="seo_description" rows="3" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('seo_description') }}</textarea>
                            @error('seo_description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                {{ __('Create') }}
                            </button>
                            <a href="{{ route('admin.tags.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
