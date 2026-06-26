<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Status — XenonBlog</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased" style="background-color: var(--color-surface); color: var(--color-text-body);">
    <div class="max-w-5xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-2" style="color: var(--color-text-heading)">System Status</h1>
        <p class="text-sm mb-8" style="color: var(--color-text-muted)">XenonBlog CMS — {{ $health['status'] }} — {{ $health['timestamp'] }}</p>

        <div class="grid gap-6 md:grid-cols-2">
            {{-- Health Checks --}}
            <div class="rounded-xl p-6" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">Service Health</h2>
                <div class="space-y-3">
                    @foreach($health['services'] as $service => $check)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium capitalize">{{ $service }}</span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                              style="background-color: {{ $check['status'] === 'healthy' ? 'var(--color-success-bg)' : 'var(--color-error-bg)' }}; color: {{ $check['status'] === 'healthy' ? 'var(--color-success)' : 'var(--color-error)' }}">
                            {{ $check['status'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- System Summary --}}
            <div class="rounded-xl p-6" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">System Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Routes</span><span class="font-semibold">{{ $routeCount }}</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Models</span><span class="font-semibold">{{ $modelCount }}</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Services</span><span class="font-semibold">{{ $serviceCount }}</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Tenants</span><span class="font-semibold">{{ $summary['tenants']['total'] }} ({{ $summary['tenants']['active'] }} active)</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Posts</span><span class="font-semibold">{{ $summary['content']['total_posts'] }} ({{ $summary['content']['published_posts'] }} published)</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Queue</span><span class="font-semibold">{{ $summary['queue']['pending'] }} pending / {{ $summary['queue']['failed'] }} failed</span></div>
                    <div class="flex justify-between"><span style="color: var(--color-text-muted)">Security Score</span><span class="font-semibold">{{ $summary['security']['score'] }}/100</span></div>
                </div>
            </div>

            {{-- Modules --}}
            <div class="md:col-span-2 rounded-xl p-6" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
                <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-heading)">Module Status</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($modules as $group => $moduleList)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--color-text-muted)">{{ $group }}</h3>
                        <div class="space-y-1">
                            @foreach($moduleList as $module => $active)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-2 h-2 rounded-full {{ $active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                <span style="color: var(--color-text-body)">{{ str_replace('_', ' ', $module) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</body>
</html>
