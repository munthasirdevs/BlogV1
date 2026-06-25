@props(['tabs' => [], 'selected' => null])

<div x-data="{ active: '{{ $selected ?? (count($tabs) > 0 ? $tabs[0]['id'] : '') }}' }" {{ $attributes }}>
    <div class="flex border-b" style="border-color: var(--color-border);" role="tablist">
        @foreach ($tabs as $tab)
            <button
                @click="active = '{{ $tab['id'] }}'"
                role="tab"
                :aria-selected="active === '{{ $tab['id'] }}'"
                :class="active === '{{ $tab['id'] }}' ? 'border-b-2' : 'border-b-2 border-transparent'"
                class="px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none"
                :style="active === '{{ $tab['id'] }}' ? 'color: var(--color-primary-600); border-color: var(--color-primary-600);' : 'color: var(--color-text-muted);'"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
