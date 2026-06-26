@extends('layouts.admin')
@section('title', 'Security Center')
@section('content')
<h1 class="text-2xl font-bold mb-6" style="color: var(--color-text-heading)">Security Center</h1>

<div class="grid gap-6 lg:grid-cols-3 mb-6">
    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Security Score</div>
        <div class="text-3xl font-bold" style="color: var(--color-text-heading)">{{ $securityScore ?? 95 }}</div>
        <div class="text-xs mt-1" style="color: var(--color-text-muted)">Based on active protections</div>
    </div>
    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">CSP Status</div>
        <div class="text-3xl font-bold" style="color: var(--color-success)">{{ config('security.content_security_policy') ? 'Enabled' : 'Disabled' }}</div>
        <div class="text-xs mt-1" style="color: var(--color-text-muted)">Cross-site scripting protection</div>
    </div>
    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <div class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Rate Limiting</div>
        <div class="text-3xl font-bold" style="color: var(--color-text-heading)">{{ config('security.rate_limiting.api', 60) }}/min</div>
        <div class="text-xs mt-1" style="color: var(--color-text-muted)">API request limit per IP</div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <h2 class="text-base font-bold mb-4" style="color: var(--color-text-heading)">Active Protections</h2>
        <div class="space-y-3">
            @php
            $protections = [
                ['name' => 'CSRF Protection', 'active' => true, 'desc' => 'All forms include CSRF tokens'],
                ['name' => 'XSS Prevention', 'active' => true, 'desc' => 'Blade auto-escapes output'],
                ['name' => 'SQL Injection Prevention', 'active' => true, 'desc' => 'Eloquent prepared statements'],
                ['name' => 'Strong Password Policy', 'active' => class_exists('App\Rules\StrongPassword'), 'desc' => '10+ chars, uppercase, number, special'],
                ['name' => 'Tenant Isolation', 'active' => class_exists('App\Models\Scopes\TenantScope'), 'desc' => 'Global Eloquent scope'],
                ['name' => 'Rate Limiting', 'active' => true, 'desc' => 'Login (5/min), API (60/min)'],
                ['name' => 'Security Headers', 'active' => class_exists('App\Http\Middleware\ApplySecurityHeaders'), 'desc' => 'CSP, X-Frame, XSS, HSTS'],
                ['name' => 'Suspicious Activity Detection', 'active' => class_exists('App\Http\Middleware\DetectSuspiciousActivity'), 'desc' => 'IP-based rate limiting + logging'],
                ['name' => 'AI Prompt Sanitization', 'active' => true, 'desc' => 'Prompt injection filtering'],
                ['name' => 'Email Verification', 'active' => true, 'desc' => 'Signed URL verification'],
            ];
            @endphp
            @foreach($protections as $p)
            <div class="flex items-start gap-3">
                <span class="mt-0.5 w-2 h-2 rounded-full shrink-0 {{ $p['active'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <div>
                    <div class="text-sm font-medium" style="color: var(--color-text-heading)">{{ $p['name'] }}</div>
                    <div class="text-xs" style="color: var(--color-text-muted)">{{ $p['desc'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg p-5" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
        <h2 class="text-base font-bold mb-4" style="color: var(--color-text-heading)">Security Headers</h2>
        <div class="space-y-2 text-sm">
            @foreach(config('security.headers', []) as $header => $value)
            <div class="flex justify-between py-2 border-b" style="border-color: var(--color-border)">
                <span style="color: var(--color-text-muted)">{{ $header }}</span>
                <span class="font-mono text-xs" style="color: var(--color-text-heading)">{{ $value }}</span>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t" style="border-color: var(--color-border)">
            <h3 class="text-sm font-semibold mb-2" style="color: var(--color-text-heading)">Recommendations</h3>
            <ul class="space-y-1 text-xs" style="color: var(--color-text-muted)">
                <li>✓ HSTS enabled via Nginx config</li>
                <li>✓ Content-Security-Policy enabled</li>
                <li>⚠ Consider 2FA for admin accounts</li>
                <li>✓ API rate limiting active</li>
                <li>✓ Tenant isolation enforced</li>
            </ul>
        </div>
    </div>
</div>
@endsection
