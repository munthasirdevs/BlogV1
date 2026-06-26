@extends('layouts.admin')
@section('title', 'Observability')
@section('content')
<h1 class="text-2xl font-bold mb-6" style="color: var(--color-text-heading)">System Observability</h1>

<div class="grid gap-4 lg:grid-cols-4 mb-6">
    <div class="rounded-lg p-4" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium" style="color: var(--color-text-muted)">Total Logs</div>
        <div class="text-2xl font-bold mt-1" style="color: var(--color-text-heading)">{{ $metrics['total_logs'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg p-4" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium" style="color: var(--color-text-muted)">Errors (24h)</div>
        <div class="text-2xl font-bold mt-1" style="color: var(--color-error)">{{ $metrics['errors_24h'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg p-4" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium" style="color: var(--color-text-muted)">Logs by Channel</div>
        <div class="mt-2 text-xs space-y-1">
            @foreach(($metrics['logs_by_channel'] ?? []) as $channel => $count)
            <div class="flex justify-between"><span>{{ $channel }}</span><span class="font-semibold">{{ $count }}</span></div>
            @endforeach
        </div>
    </div>
    <div class="rounded-lg p-4" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium" style="color: var(--color-text-muted)">Logs by Level</div>
        <div class="mt-2 text-xs space-y-1">
            @foreach(($metrics['logs_by_level'] ?? []) as $level => $count)
            <div class="flex justify-between">
                <span class="capitalize">{{ $level }}</span>
                <span class="font-semibold">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <h2 class="text-base font-bold mb-3" style="color: var(--color-text-heading)">Recent Errors</h2>
        @forelse($recentErrors as $error)
        <div class="py-2 border-b text-xs" style="border-color: var(--color-border)">
            <div class="flex items-center gap-2">
                <span class="px-1.5 py-0.5 rounded font-semibold text-[10px] uppercase"
                      style="background-color: var(--color-error-bg); color: var(--color-error)">{{ $error['level'] }}</span>
                <span style="color: var(--color-text-muted)">{{ \Carbon\Carbon::parse($error['created_at'])->diffForHumans() }}</span>
            </div>
            <div class="mt-1" style="color: var(--color-text-heading)">{{ $error['message'] }}</div>
        </div>
        @empty
        <p class="text-sm py-4" style="color: var(--color-text-muted)">No recent errors.</p>
        @endforelse
    </div>

    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <h2 class="text-base font-bold mb-3" style="color: var(--color-text-heading)">System Channels</h2>
        <p class="text-xs mb-3" style="color: var(--color-text-muted)">
            All application logs are written to the <code class="font-mono">system_logs</code> table via the custom Monolog handler.
        </p>
        <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 py-2 border-b" style="border-color: var(--color-border)">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span style="color: var(--color-text-body)">observability channel — captures all warnings and errors</span>
            </div>
            <div class="flex items-center gap-2 py-2 border-b" style="border-color: var(--color-border)">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span style="color: var(--color-text-body)">system channel — Monolog handler writes to DB</span>
            </div>
            <div class="flex items-center gap-2 py-2 border-b" style="border-color: var(--color-border)">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span style="color: var(--color-text-body)">security channel — daily log file</span>
            </div>
            <div class="flex items-center gap-2 py-2 border-b" style="border-color: var(--color-border)">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <span style="color: var(--color-text-body)">ai channel — AI processing logs (90 day retention)</span>
            </div>
        </div>
    </div>
</div>
@endsection
