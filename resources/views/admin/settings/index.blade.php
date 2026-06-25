@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<h1 class="text-2xl font-bold mb-6">Site Settings</h1>
<form action="{{ route('admin.settings.update') }}" method="POST" class="rounded-lg shadow p-6 max-w-lg space-y-4" style="background-color: var(--color-surface-card)">
    @csrf
    @method('PUT')
    <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Site Title</label><input type="text" name="site_title" value="{{ $settings['site_title'] ?? config('app.name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Site Description</label><textarea name="site_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200">{{ $settings['site_description'] ?? '' }}</textarea></div>
    <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Posts Per Page</label><input type="number" name="posts_per_page" value="{{ $settings['posts_per_page'] ?? 12 }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Save Settings</button>
</form>
@endsection
