@extends('layouts.admin')
@section('title', 'Plugins')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">Plugins</h1>
    <button @click="$refs.registerForm.classList.toggle('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-xs font-semibold hover:bg-indigo-500">Register Plugin</button>
</div>

<div x-ref="registerForm" class="hidden mb-6 rounded-lg p-6" style="background-color: var(--color-surface-card); border: 1px solid var(--color-border);">
    <h3 class="text-sm font-semibold mb-4" style="color: var(--color-text-heading)">Register New Plugin</h3>
    <form action="{{ route('admin.plugins.register') }}" method="POST" class="grid grid-cols-2 gap-4">
        @csrf
        <div><label class="block text-xs font-medium mb-1">Slug</label><input type="text" name="slug" required class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Name</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Version</label><input type="text" name="version" required class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Author</label><input type="text" name="author" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div class="col-span-2"><label class="block text-xs font-medium mb-1">Description</label><textarea name="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
        <div class="col-span-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-xs font-semibold hover:bg-indigo-500">Register</button>
        </div>
    </form>
</div>

<div class="rounded-lg shadow overflow-hidden" style="background-color: var(--color-surface-card)">
    <table class="w-full">
        <thead style="background-color: var(--color-surface-elevated)">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Version</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Author</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-muted)">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y" style="border-color: var(--color-border)">
            @forelse($plugins as $plugin)
            <tr>
                <td class="px-4 py-3 text-sm font-medium" style="color: var(--color-text-heading)">{{ $plugin->name }}</td>
                <td class="px-4 py-3 text-sm" style="color: var(--color-text-muted)">v{{ $plugin->version }}</td>
                <td class="px-4 py-3 text-sm" style="color: var(--color-text-muted)">{{ $plugin->author ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold"
                          style="background-color: {{ $plugin->status === 'enabled' ? 'var(--color-success-bg)' : ($plugin->status === 'installed' ? 'var(--color-info-bg)' : 'var(--color-surface-elevated)') }};
                                  color: {{ $plugin->status === 'enabled' ? 'var(--color-success)' : ($plugin->status === 'installed' ? 'var(--color-info)' : 'var(--color-text-muted)') }};">
                        {{ ucfirst($plugin->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if($plugin->status === 'disabled')
                    <form action="{{ route('admin.plugins.enable', $plugin) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-green-900" style="color: var(--color-success)">Enable</button>
                    </form>
                    @elseif($plugin->status === 'enabled')
                    <form action="{{ route('admin.plugins.disable', $plugin) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-red-900" style="color: var(--color-error)">Disable</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-sm" style="color: var(--color-text-muted)">No plugins registered.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
