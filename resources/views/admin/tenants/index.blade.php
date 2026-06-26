@extends('layouts.admin')
@section('title', 'Tenants')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">Tenants</h1>
    <a href="{{ route('admin.tenants.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-xs font-semibold hover:bg-indigo-500">New Tenant</a>
</div>
<div class="rounded-lg shadow overflow-hidden" style="background-color: var(--color-surface-card)">
    <table class="w-full">
        <thead style="background-color: var(--color-surface-elevated)">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Domain</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Posts</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y" style="border-color: var(--color-border)">
            @forelse($tenants as $tenant)
            <tr>
                <td class="px-4 py-3 text-sm font-medium" style="color: var(--color-text-heading)">{{ $tenant->name }}</td>
                <td class="px-4 py-3 text-sm" style="color: var(--color-text-muted)">{{ $tenant->domain ?? '—' }}</td>
                <td class="px-4 py-3 text-sm" style="color: var(--color-text-muted)">{{ $tenant->posts_count ?? 0 }}</td>
                <td class="px-4 py-3">
                    @if($tenant->is_active)
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold" style="background-color: var(--color-success-bg); color: var(--color-success)">Active</span>
                    @else
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold" style="background-color: var(--color-error-bg); color: var(--color-error)">Suspended</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm">
                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-sm" style="color: var(--color-text-muted)">No tenants yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
