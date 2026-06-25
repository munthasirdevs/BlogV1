<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight" style="color: var(--color-text-heading)">
            {{ __('Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{-- Stats Cards --}}
            <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Total Views (30d)') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ number_format($data['totalViews']) }}</div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __("Today's Views") }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ number_format($data['todayViews']) }}</div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="p-6">
                        <div class="text-sm font-medium" style="color: var(--color-text-muted)">{{ __('Avg Daily Views') }}</div>
                        <div class="mt-2 text-3xl font-bold" style="color: var(--color-text-heading)">{{ number_format($data['avgDailyViews']) }}</div>
                    </div>
                </div>
            </div>

            {{-- Views Per Day Chart --}}
            <div class="mb-6 overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                    <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Views Per Day (Last 30 Days)') }}</h3>
                </div>
                <div class="p-6">
                    @php $maxChart = max($data['chartData']) ?: 1; @endphp
                    <div class="flex items-end gap-1" style="height: 200px;">
                        @foreach($data['chartData'] as $date => $count)
                            <div class="flex flex-1 flex-col items-center">
                                <div class="w-full rounded-t" style="background-color: var(--color-primary-500)" hover:bg-indigo-600 style="height: {{ ($count / $maxChart) * 180 }}px; min-height: {{ $count > 0 ? '2px' : '0' }};" title="{{ $date }}: {{ $count }}"></div>
                                @if($loop->iteration % 5 === 0 || $loop->last)
                                    <span class="mt-1 text-xs" style="color: var(--color-text-muted)">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Top Posts --}}
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                        <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Top Posts by Views') }}</h3>
                    </div>
                    <div class="p-6">
                        @if($data['topPosts']->isNotEmpty())
                            <table class="min-w-full divide-y" style="border-color: var(--color-border)">
                                <thead style="background-color: var(--color-surface-elevated)">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Title') }}</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Views') }}</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider" style="color: var(--color-text-muted)">{{ __('Shares') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="border-color: var(--color-border)">
                                    @foreach($data['topPosts'] as $post)
                                        <tr>
                                            <td class="px-4 py-2 text-sm" style="color: var(--color-text-heading)">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</td>
                                            <td class="px-4 py-2 text-right text-sm" style="color: var(--color-text-muted)">{{ number_format($post->views_count) }}</td>
                                            <td class="px-4 py-2 text-right text-sm" style="color: var(--color-text-muted)">{{ number_format($post->shares_count ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No data yet.') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Device Breakdown --}}
                <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                    <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                        <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Device Breakdown') }}</h3>
                    </div>
                    <div class="p-6">
                        @php $deviceTotal = array_sum($data['deviceBreakdown']) ?: 1; @endphp
                        @if(!empty($data['deviceBreakdown']))
                            @foreach(['desktop', 'tablet', 'mobile'] as $device)
                                @php
                                    $count = $data['deviceBreakdown'][$device] ?? 0;
                                    $pct = round(($count / $deviceTotal) * 100);
                                @endphp
                                <div class="mb-4">
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="font-medium" style="color: var(--color-text-body)">{{ ucfirst($device) }}</span>
                                        <span style="color: var(--color-text-muted)">{{ $pct }}% ({{ number_format($count) }})</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-200">
                                        <div class="h-2 rounded-full {{ $device === 'desktop' ? 'bg-blue-500' : ($device === 'tablet' ? 'bg-green-500' : 'bg-purple-500') }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No data yet.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Traffic Sources --}}
            <div class="overflow-hidden rounded-lg shadow-sm" style="background-color: var(--color-surface-card)">
                <div class="border-b px-6 py-4" style="border-color: var(--color-border)">
                    <h3 class="text-base font-semibold" style="color: var(--color-text-heading)">{{ __('Traffic Sources') }}</h3>
                </div>
                <div class="p-6">
                    @if($data['trafficSources']->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($data['trafficSources'] as $source)
                                <div class="flex items-center justify-between border-b border-gray-100 pb-2 last:border-0">
                                    <span class="text-sm font-medium" style="color: var(--color-text-body)">{{ ucfirst($source->source) }}</span>
                                    <span class="text-sm" style="color: var(--color-text-muted)">{{ number_format($source->count) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm" style="color: var(--color-text-muted)">{{ __('No data yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
