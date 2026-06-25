@props(['variant' => 'default'])

@php
$styles = [
    'default' => 'background-color: var(--color-surface-elevated); color: var(--color-text-muted);',
    'primary' => 'background-color: var(--color-primary-100); color: var(--color-primary-700);',
    'success' => 'background-color: var(--color-success-bg); color: var(--color-success);',
    'warning' => 'background-color: var(--color-warning-bg); color: var(--color-warning);',
    'error' => 'background-color: var(--color-error-bg); color: var(--color-error);',
    'info' => 'background-color: var(--color-info-bg); color: var(--color-info);',
][$variant] ?? 'background-color: var(--color-surface-elevated); color: var(--color-text-muted);';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']) }} style="{{ $styles }}">
    {{ $slot }}
</span>
