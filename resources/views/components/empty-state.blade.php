@props(['icon' => null, 'title' => 'No data', 'description' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4']) }}>
    @if ($icon)
        <div class="mb-4" style="color: var(--color-text-muted);">{{ $icon }}</div>
    @else
        <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    @endif
    <h3 class="text-lg font-medium" style="color: var(--color-text-heading);">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-center max-w-md" style="color: var(--color-text-muted);">{{ $description }}</p>
    @endif
    @if ($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
