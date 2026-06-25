@props(['disabled' => false, 'placeholder' => 'Search...'])

<div class="relative">
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--color-text-muted);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
    <input
        type="search"
        @disabled($disabled)
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'pl-10 rounded-lg shadow-sm w-full focus:ring-2 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)] disabled:opacity-50 disabled:cursor-not-allowed']) }}
        style="background-color: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text-body);"
    >
</div>
