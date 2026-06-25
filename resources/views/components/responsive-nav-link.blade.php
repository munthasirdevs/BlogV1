@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out';
$activeStyle = 'border-color: var(--color-primary-500); color: var(--color-primary-700); background-color: var(--color-primary-50);';
$inactiveStyle = 'color: var(--color-text-muted);';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ ($active ?? false) ? $activeStyle : $inactiveStyle }}" onmouseover="this.style.backgroundColor='var(--color-surface-elevated)'" onmouseout="this.style.backgroundColor='{{ ($active ?? false) ? 'var(--color-primary-50)' : 'transparent' }}'">
    {{ $slot }}
</a>
