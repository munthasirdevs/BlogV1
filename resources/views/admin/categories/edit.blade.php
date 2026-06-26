<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
                {{ __('Edit Category') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                    {{ __('New Category') }}
                </a>
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                    {{ __('All Categories') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md p-4 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success)">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Main Form --}}
                <div class="lg:col-span-2 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6" style="color: var(--color-text-heading)">
                        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Name') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                                       class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                                @error('name') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="slug" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Slug') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}"
                                       class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)" required>
                                @error('slug') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="parent_id" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Parent Category') }}</label>
                                <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                    <option value="">{{ __('None (Top Level)') }}</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                @if($category->children->count())
                                <p class="mt-1 text-xs" style="color: var(--color-text-muted)">{{ __('This category has') }} {{ $category->children->count() }} {{ __('subcategories. Changing the parent will move them too.') }}</p>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label for="short_description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Short Description') }}</label>
                                    <textarea name="short_description" id="short_description" rows="3"
                                              class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('short_description', $category->short_description) }}</textarea>
                                    @error('short_description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="full_description" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Full Description') }}</label>
                                    <textarea name="full_description" id="full_description" rows="3"
                                              class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">{{ old('full_description', $category->full_description) }}</textarea>
                                    @error('full_description') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="mb-4">
                                    <label for="status" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                        <option value="draft" {{ old('status', $category->status) == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                        <option value="published" {{ old('status', $category->status) == 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                        <option value="archived" {{ old('status', $category->status) == 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                                        <option value="hidden" {{ old('status', $category->status) == 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
                                    </select>
                                    @error('status') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="sort_order" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Sort Order') }}</label>
                                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                                           class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)">
                                    @error('sort_order') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4" x-data="{ color: '{{ old('color', $category->color) }}' }">
                                    <label for="color" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Color') }}</label>
                                    <div class="mt-1 flex gap-2">
                                        <input type="color" name="color" x-model="color" value="{{ old('color', $category->color) }}"
                                               class="h-10 w-12 rounded cursor-pointer border" style="border-color: var(--color-border)">
                                        <input type="text" name="color" x-model="color"
                                               class="block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)"
                                               placeholder="#3b82f6">
                                    </div>
                                    @error('color') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="icon" class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Icon (emoji or SVG path)') }}</label>
                                    <div class="mt-1 flex gap-2" x-data="{ iconPreview: '{{ old('icon', $category->icon) }}' }">
                                        <input type="text" name="icon" id="icon" value="{{ old('icon', $category->icon) }}"
                                               class="block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="border-color: var(--color-border)"
                                               placeholder="🔧 or M12..." x-model="iconPreview">
                                        <span class="inline-flex items-center px-3 rounded-md border" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);" x-html="iconPreview"></span>
                                    </div>
                                    @error('icon') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="featured" value="1" {{ old('featured', $category->featured) ? 'checked' : '' }}
                                           class="rounded shadow-sm focus:ring-indigo-500" style="border-color: var(--color-border); color: var(--color-primary-600)">
                                    <span class="ml-2 text-sm" style="color: var(--color-text-body)">{{ __('Featured Category') }}</span>
                                </label>
                                @error('featured') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4" x-data="{ preview: null }">
                                <label class="block text-sm font-medium" style="color: var(--color-text-body)">{{ __('Featured Image') }}</label>
                                @if($category->image)
                                <div class="mt-2 mb-2">
                                    <img src="{{ $category->image }}" alt="{{ $category->name }}" class="h-32 w-48 rounded-lg object-cover">
                                    <label class="mt-1 inline-flex items-center text-xs" style="color: var(--color-text-muted)">
                                        <input type="checkbox" name="remove_image" value="1"> {{ __('Remove image') }}
                                    </label>
                                </div>
                                @endif
                                <input type="file" name="image" accept="image/jpg,image/jpeg,image/png,image/webp"
                                       class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                @error('image') <p class="mt-1 text-sm" style="color: var(--color-error)">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t" style="border-color: var(--color-border)">
                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                    {{ __('Update Category') }}
                                </button>
                                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:bg-gray-300" style="color: var(--color-text-body)">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b px-4 py-3" style="border-color: var(--color-border)">
                            <h3 class="text-sm font-semibold" style="color: var(--color-text-body)">{{ __('Category Stats') }}</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between text-sm" style="color: var(--color-text-muted)">
                                <span>{{ __('Articles') }}</span>
                                <span class="font-semibold" style="color: var(--color-text-heading)">{{ $category->article_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm" style="color: var(--color-text-muted)">
                                <span>{{ __('Created') }}</span>
                                <span class="font-semibold" style="color: var(--color-text-heading)">{{ $category->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between text-sm" style="color: var(--color-text-muted)">
                                <span>{{ __('Updated') }}</span>
                                <span class="font-semibold" style="color: var(--color-text-heading)">{{ $category->updated_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between text-sm" style="color: var(--color-text-muted)">
                                <span>{{ __('Creator') }}</span>
                                <span class="font-semibold" style="color: var(--color-text-heading)">{{ $category->creator?->name ?? '—' }}</span>
                            </div>
                            @if($category->parent)
                            <div class="flex justify-between text-sm" style="color: var(--color-text-muted)">
                                <span>{{ __('Parent') }}</span>
                                <a href="{{ route('admin.categories.edit', $category->parent) }}" class="font-semibold hover:underline" style="color: var(--color-primary-600)">{{ $category->parent->name }}</a>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($category->children->count())
                    <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                        <div class="border-b px-4 py-3" style="border-color: var(--color-border)">
                            <h3 class="text-sm font-semibold" style="color: var(--color-text-body)">{{ __('Subcategories') }}</h3>
                        </div>
                        <div class="p-2 space-y-1">
                            @foreach($category->children as $child)
                            <a href="{{ route('admin.categories.edit', $child) }}"
                               class="block rounded-md px-3 py-2 text-sm hover:bg-gray-50" style="color: var(--color-text-body)">
                                {{ $child->name }}
                                <span class="text-xs" style="color: var(--color-text-muted)">({{ $child->article_count }})</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                          onsubmit="return confirm('{{ __('Delete this category permanently? Articles will be uncategorized.') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500">
                            {{ __('Delete Category') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
