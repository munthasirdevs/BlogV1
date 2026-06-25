@props(['text' => '', 'position' => 'top'])

@php
$positionClasses = [
    'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
    'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
    'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
];
$pos = $positionClasses[$position] ?? $positionClasses['top'];
@endphp

<div x-data="{ show: false }" class="relative inline-flex" @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false">
    <div x-show="show" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute z-50 {{ $pos }} px-2 py-1 text-xs rounded-md whitespace-nowrap pointer-events-none" style="background-color: var(--color-text-heading); color: var(--color-surface);" role="tooltip">
        {{ $text }}
    </div>
    {{ $slot }}
</div>
