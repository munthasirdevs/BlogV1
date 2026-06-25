@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6" style="color: var(--color-text-heading)">Contact Us</h1>
    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
        @csrf
        <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Name</label><input type="text" name="name" required class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 border" style="border-color: var(--color-border)"></div>
        <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Email</label><input type="email" name="email" required class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 border" style="border-color: var(--color-border)"></div>
        <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Subject</label><input type="text" name="subject" class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 border" style="border-color: var(--color-border)"></div>
        <div><label class="block text-sm font-medium" style="color: var(--color-text-body)">Message</label><textarea name="message" rows="5" required class="mt-1 block w-full rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 border" style="border-color: var(--color-border)"></textarea></div>
        <button type="submit" class="text-white px-6 py-2 rounded-md" style="background-color: var(--color-primary-600)">Send Message</button>
    </form>
</div>
@endsection
