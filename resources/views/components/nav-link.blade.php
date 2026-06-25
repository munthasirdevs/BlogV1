@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
$activeStyle = 'border-color: var(--color-primary-500); color: var(--color-text-heading);';
$inactiveStyle = 'color: var(--color-text-muted);';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ ($active ?? false) ? $activeStyle : $inactiveStyle }}" onmouseover="this.style.color='var(--color-text-heading)'" onmouseout="this.style.color='{{ ($active ?? false) ? 'var(--color-text-heading)' : 'var(--color-text-muted)' }}'">
    {{ $slot }}
</a>
