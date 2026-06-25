@props(['code' => '404', 'title' => 'Page not found', 'description' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-16 px-4']) }}>
    <span class="text-6xl font-bold" style="color: var(--color-primary-600);">{{ $code }}</span>
    <h1 class="mt-4 text-2xl font-bold" style="color: var(--color-text-heading);">{{ $title }}</h1>
    @if ($description)
        <p class="mt-2 text-sm text-center max-w-md" style="color: var(--color-text-muted);">{{ $description }}</p>
    @endif
    @if ($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
