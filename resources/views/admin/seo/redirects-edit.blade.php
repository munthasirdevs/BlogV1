@extends('layouts.admin')
@section('title', 'Edit Redirect')
@section('content')
<h1 class="text-2xl font-bold mb-6">Edit Redirect</h1>
<form action="{{ route('admin.seo.redirects.update', $redirect) }}" method="POST" class="bg-white rounded-lg shadow p-6 max-w-lg space-y-4">
    @csrf
    @method('PUT')
    <div><label class="block text-sm font-medium text-gray-700">Old URL</label><input type="text" name="old_url" value="{{ old('old_url', $redirect->old_url) }}" placeholder="/old-page" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <div><label class="block text-sm font-medium text-gray-700">New URL</label><input type="text" name="new_url" value="{{ old('new_url', $redirect->new_url) }}" placeholder="/new-page" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
    <div><label class="block text-sm font-medium text-gray-700">Type</label><select name="redirect_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"><option value="301" {{ $redirect->redirect_type == '301' ? 'selected' : '' }}>301 (Permanent)</option><option value="302" {{ $redirect->redirect_type == '302' ? 'selected' : '' }}>302 (Temporary)</option></select></div>
    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Update Redirect</button>
</form>
@endsection
