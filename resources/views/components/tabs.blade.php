@props(['tabs' => [], 'selected' => null])

@php $initialTab = $selected ?? (count($tabs) > 0 ? $tabs[0]['id'] : ''); @endphp
<div x-data="{ active: @js($initialTab) }" {{ $attributes }}>
    <div class="flex border-b" style="border-color: var(--color-border);" role="tablist">
        @foreach ($tabs as $tab)
            <button
                @click="active = @js($tab['id'])"
                role="tab"
                :aria-selected="active === @js($tab['id'])"
                :class="active === @js($tab['id']) ? 'border-b-2' : 'border-b-2 border-transparent'"
                class="px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none"
                :style="active === @js($tab['id']) ? 'color: var(--color-primary-600); border-color: var(--color-primary-600);' : 'color: var(--color-text-muted);'"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
