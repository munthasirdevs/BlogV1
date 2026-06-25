@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Users</h1>
    <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">New User</a>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Roles</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($users as $user)
            <tr>
                <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                <td class="px-4 py-3 text-sm">@foreach($user->getRoleNames() as $role)<span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded">{{ $role }}</span> @endforeach</td>
                <td class="px-4 py-3 text-sm">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</td>
                <td class="px-4 py-3 text-sm"><a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
