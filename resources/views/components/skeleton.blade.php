@props(['type' => 'card', 'count' => 1])

@php
$shapes = [
    'card' => '
        <div class="animate-pulse bg-white rounded-lg overflow-hidden shadow-sm">
            <div class="bg-gray-200 h-48 w-full"></div>
            <div class="p-4 space-y-3">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                <div class="h-3 bg-gray-200 rounded w-full"></div>
            </div>
        </div>',
    'text' => '
        <div class="animate-pulse space-y-3">
            <div class="h-4 bg-gray-200 rounded w-full"></div>
            <div class="h-4 bg-gray-200 rounded w-5/6"></div>
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        </div>',
    'image' => '
        <div class="animate-pulse bg-gray-200 rounded-lg"
             style="aspect-ratio: 16/9; width: 100%;"></div>',
];
@endphp

@for ($i = 0; $i < $count; $i++)
    {!! $shapes[$type] ?? $shapes['card'] !!}
@endfor
