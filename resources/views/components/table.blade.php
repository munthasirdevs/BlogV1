@props(['headers' => [], 'rows' => [], 'striped' => false])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg shadow-sm']) }} style="border: 1px solid var(--color-border);">
    <table class="min-w-full divide-y" style="border-color: var(--color-border);">
        @if (count($headers) > 0)
            <thead>
                <tr style="background-color: var(--color-surface-elevated);">
                    @foreach ($headers as $header)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted);">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y" style="border-color: var(--color-border);">
            @forelse ($rows as $row)
                <tr style="background-color: var(--color-surface-card);" class="{{ $striped && $loop->even ? 'opacity-80' : '' }} hover:opacity-90 transition-opacity">
                    @foreach ($row as $cell)
                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--color-text-body);">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-6 py-12 text-center text-sm" style="color: var(--color-text-muted);">No results found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
