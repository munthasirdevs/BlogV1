@extends('layouts.admin')
@section('title', 'System Information')
@section('content')
<h1 class="text-2xl font-bold mb-6" style="color: var(--color-text-heading)">System Information</h1>
<div class="grid gap-6 md:grid-cols-2">
    <div class="rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
        <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">Application</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">PHP Version</span><span class="font-semibold">{{ $phpVersion }}</span></div>
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Laravel Version</span><span class="font-semibold">{{ $laravelVersion }}</span></div>
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Environment</span><span class="font-semibold">{{ $environment }}</span></div>
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Debug Mode</span><span class="font-semibold">{{ $debugMode ? 'Enabled' : 'Disabled' }}</span></div>
            <div class="flex justify-between py-2" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">App URL</span><span class="font-semibold">{{ $appUrl }}</span></div>
        </div>
    </div>
    <div class="rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
        <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">Drivers</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Database</span><span class="font-semibold">{{ $dbConnection }}</span></div>
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Cache</span><span class="font-semibold">{{ $cacheDriver }}</span></div>
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Queue</span><span class="font-semibold">{{ $queueDriver }}</span></div>
            <div class="flex justify-between py-2" style="border-color: var(--color-border)"><span style="color: var(--color-text-muted)">Session</span><span class="font-semibold">{{ $sessionDriver }}</span></div>
        </div>
    </div>
    <div class="md:col-span-2 rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
        <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">System Commands</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <form action="{{ route('admin.commands.flush-cache') }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-3 rounded-lg text-sm font-semibold text-white text-center" style="background-color: var(--color-error)" onclick="return confirm('Flush all caches?')">Flush Cache</button></form>
            <form action="{{ route('admin.commands.warm-cache') }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-3 rounded-lg text-sm font-semibold text-white text-center" style="background-color: var(--color-info)">Warm Cache</button></form>
            <form action="{{ route('admin.commands.rebuild-search') }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-3 rounded-lg text-sm font-semibold text-white text-center" style="background-color: var(--color-primary-600)">Rebuild Search Index</button></form>
            <form action="{{ route('admin.commands.optimize') }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-3 rounded-lg text-sm font-semibold text-white text-center" style="background-color: var(--color-success)">Optimize App</button></form>
        </div>
    </div>
</div>
@endsection
