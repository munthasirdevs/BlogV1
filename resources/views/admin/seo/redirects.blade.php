@extends('layouts.admin')
@section('title', 'Redirects')
@section('content')
<div class="flex justify-between items-center mb-6"><h1 class="text-2xl font-bold">Redirects</h1><a href="{{ route('admin.seo.redirects.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">New Redirect</a></div>
<div class="bg-white rounded-lg shadow overflow-hidden"><table class="w-full"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Old URL</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-500">New URL</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Type</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Hits</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Actions</th></tr></thead><tbody class="divide-y divide-gray-200">@foreach($redirects as $r)<tr><td class="px-4 py-3 text-sm">{{ $r->old_url }}</td><td class="px-4 py-3 text-sm text-gray-600">{{ $r->new_url }}</td><td class="px-4 py-3 text-sm">{{ $r->redirect_type }}</td><td class="px-4 py-3 text-sm">{{ $r->hit_count }}</td><td class="px-4 py-3 text-sm"><a href="{{ route('admin.seo.redirects.edit', $r) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a></td></tr>@endforeach</tbody></table></div>
{{ $redirects->links() }}
@endsection
