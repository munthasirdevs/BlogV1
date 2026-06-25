@extends('layouts.admin')
@section('title', 'AI Tools')
@section('content')
<h1 class="text-2xl font-bold mb-6">AI Writing Assistant</h1>
<div class="rounded-lg shadow p-6" style="background-color: var(--color-surface-card)">
    <form action="{{ route('admin.ai.generate') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium" style="color: var(--color-text-body)">Content Type</label>
            <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200">
                <option value="article">Article</option>
                <option value="title">Title Suggestions</option>
                <option value="meta">SEO Meta</option>
                <option value="keywords">Keywords</option>
                <option value="summary">Summary</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" style="color: var(--color-text-body)">Prompt / Topic</label>
            <textarea name="prompt" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200" placeholder="Describe what you want to write about..." required></textarea>
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Generate</button>
    </form>
</div>

@if(isset($history) && $history->count() > 0)
<div class="mt-8">
    <h2 class="text-xl font-bold mb-4">Recent Generations</h2>
    <div class="rounded-lg shadow overflow-hidden" style="background-color: var(--color-surface-card)">
        <table class="w-full">
            <thead style="background-color: var(--color-surface-elevated)">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Prompt</th>
                    <th class="px-4 py-3 text-left text-sm font-medium" style="color: var(--color-text-muted)">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: var(--color-border)">
                @foreach($history as $gen)
                <tr>
                    <td class="px-4 py-3 text-sm">{{ ucfirst($gen->generation_type) }}</td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-body)">{{ Str::limit($gen->prompt, 60) }}</td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-muted)">{{ $gen->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $history->links() }}
</div>
@endif
@endsection
