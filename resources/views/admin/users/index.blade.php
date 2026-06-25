@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Users</h1>
    <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">New User</a>
</div>
<div class="rounded-lg shadow overflow-hidden" style="background-color: var(--color-surface-card)">
    <table class="w-full">
        <thead style="background-color: var(--color-surface-elevated)">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Email</th>
                <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Roles</th>
                <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y" style="border-color: var(--color-border)">
            @foreach($users as $user)
            <tr>
                <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                <td class="px-4 py-3 text-sm" style="color: var(--color-text-body)">{{ $user->email }}</td>
                <td class="px-4 py-3 text-sm">@foreach($user->getRoleNames() as $role)<span class="text-xs px-2 py-1 rounded" style="background-color: var(--color-primary-50); color: var(--color-primary-600)">{{ $role }}</span> @endforeach</td>
                <td class="px-4 py-3 text-sm">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</td>
                <td class="px-4 py-3 text-sm"><a href="{{ route('admin.users.edit', $user) }}" class="hover:text-indigo-900" style="color: var(--color-primary-600)">Edit</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
