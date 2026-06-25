@props(['padding' => '6', 'hover' => false])

<div {{ $attributes->merge(['class' => 'rounded-lg shadow-sm' . ($hover ? ' hover:shadow-md transition-shadow duration-200' : '')]) }} style="background-color: var(--color-surface-card); border: 1px solid var(--color-border); padding: {{ is_numeric($padding) ? $padding * 4 : $padding }}px;">
    {{ $slot }}
</div>
