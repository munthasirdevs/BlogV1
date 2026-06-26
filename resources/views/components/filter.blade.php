@props(['options' => [], 'selected' => null, 'placeholder' => 'Filter...', 'name' => 'filter'])

<div x-data="{
    open: false,
    selected: @js($selected),
    options: @js($options)
}" class="relative" {{ $attributes }}>
    <button
        @click="open = !open"
        type="button"
        class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]"
        style="background-color: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text-body);"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <span class="flex-1 text-left" x-text="selected ? options.find(o => o.value === selected)?.label || @js($placeholder) : @js($placeholder)"></span>
        <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" @click.away="open = false" class="absolute z-10 mt-1 w-full rounded-lg shadow-lg py-1" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        @foreach ($options as $option)
            <button
                type="button"
                @click="selected = @js($option['value']); open = false"
                class="block w-full text-left px-4 py-2 text-sm focus:outline-none"
                :class="selected === @js($option['value']) ? 'font-medium' : ''"
                :style="selected === @js($option['value']) ? 'color: var(--color-primary-600); background-color: var(--color-primary-50);' : 'color: var(--color-text-body);'"
                x-on:mouseenter="$el.style.backgroundColor = 'var(--color-surface-elevated)'"
                x-on:mouseleave="$el.style.backgroundColor = selected === @js($option['value']) ? 'var(--color-primary-50)' : 'transparent'"
            >{{ $option['label'] }}</button>
        @endforeach
    </div>
    <input type="hidden" name="{{ $name }}" :value="selected">
</div>
