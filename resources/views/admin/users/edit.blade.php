@extends('layouts.admin')
@section('title', 'Edit User: ' . $user->name)
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold" style="color: var(--color-text-heading)">Edit User: {{ $user->name }}</h1>
    <a href="{{ route('admin.users.index') }}" class="text-sm" style="color: var(--color-primary-600)">&larr; Back to Users</a>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-body)">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-body)">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-body)">Password <span class="text-xs text-gray-400">(leave empty to keep current)</span></label>
                <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-body)">Confirm Password</label>
                <input type="password" name="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-body)">Bio</label>
                <textarea name="bio" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium" style="color: var(--color-text-body)">Role</label>
                    <select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium" style="color: var(--color-text-body)">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="banned" {{ $user->status === 'banned' ? 'selected' : '' }}>Banned</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Update User</button>
        </form>
    </div>
    <div class="space-y-4">
        <div class="rounded-lg shadow p-6 text-center" style="background-color: var(--color-surface-card)">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full mx-auto mb-3">
            <h3 class="font-semibold" style="color: var(--color-text-heading)">{{ $user->name }}</h3>
            <p class="text-sm" style="color: var(--color-text-muted)">{{ $user->email }}</p>
            @if($user->last_login_at)
            <p class="text-xs mt-2" style="color: var(--color-text-muted)">Last login: {{ $user->last_login_at->diffForHumans() }}</p>
            @endif
        </div>
        <div class="rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
            <h4 class="text-sm font-semibold mb-3" style="color: var(--color-text-heading)">Avatar</h4>
            <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="file" name="avatar" accept="image/jpg,image/png,image/webp" class="block w-full text-sm">
                @if($user->avatar)
                <label class="mt-2 inline-flex items-center text-xs" style="color: var(--color-text-muted)"><input type="checkbox" name="remove_avatar" value="1"> Remove avatar</label>
                @endif
                <button type="submit" class="mt-3 w-full bg-gray-200 px-3 py-2 rounded-md text-xs font-semibold hover:bg-gray-300" style="color: var(--color-text-body)">Upload Avatar</button>
            </form>
        </div>
    </div>
</div>
@endsection
