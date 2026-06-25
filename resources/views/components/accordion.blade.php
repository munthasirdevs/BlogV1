@props(['items' => [], 'multiple' => false])

<div x-data="{ openItems: {{ $multiple ? '[]' : 'null' }} }" {{ $attributes->merge(['class' => 'divide-y rounded-lg']) }} style="border: 1px solid var(--color-border);">
    @foreach ($items as $index => $item)
        @php $id = $item['id'] ?? $index; @endphp
        <div x-data="{ isOpen: false }" x-init="isOpen = {{ $index === 0 ? 'true' : 'false' }}">
            <button
                @click="isOpen = !isOpen"
                class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary-500)]"
                style="color: var(--color-text-heading); background-color: var(--color-surface-card);"
                :aria-expanded="isOpen"
            >
                <span>{{ $item['title'] ?? $id }}</span>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen" x-collapse class="px-4 pb-3 text-sm" style="color: var(--color-text-body);">
                {{ $item['content'] ?? '' }}
            </div>
        </div>
    @endforeach
</div>
