@extends('layouts.admin')
@section('title', 'Edit User')
@section('content')
<h1 class="text-2xl font-bold mb-6">Edit User: {{ $user->name }}</h1>
<form action="{{ route('admin.users.update', $user) }}" method="POST" class="bg-white rounded-lg shadow p-6 max-w-lg space-y-4">
    @csrf
    @method('PUT')
    <div><label class="block text-sm font-medium text-gray-700">Name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <div><label class="block text-sm font-medium text-gray-700">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <div><label class="block text-sm font-medium text-gray-700">Role</label><select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200">@foreach($roles as $role)<option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>@endforeach</select></div>
    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Update User</button>
</form>
@endsection
