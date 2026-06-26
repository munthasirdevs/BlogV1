@props(['variant' => 'info', 'id' => ''])

@php
$icons = [
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>',
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];

$colors = [
    'success' => 'var(--color-success)',
    'error' => 'var(--color-error)',
    'warning' => 'var(--color-warning)',
    'info' => 'var(--color-info)',
];

$color = $colors[$variant] ?? $colors['info'];
$icon = $icons[$variant] ?? $icons['info'];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transform ease-out duration-300"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm']) }}
    style="background-color: var(--color-surface-card); border: 1px solid var(--color-border); color: var(--color-text-body);"
    role="alert"
    id="{{ $id }}"
>
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: {{ $color }}">{{ $icon }}</svg>
    <span class="flex-1">{{ $slot }}</span>
    <button @click="show = false" class="shrink-0 p-1 rounded-md hover:opacity-75 focus:outline-none" aria-label="Dismiss" style="color: var(--color-text-muted);">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
    </button>
</div>
