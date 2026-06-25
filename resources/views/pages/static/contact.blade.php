@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6">Contact Us</h1>
    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Name</label><input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
        <div><label class="block text-sm font-medium text-gray-700">Email</label><input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
        <div><label class="block text-sm font-medium text-gray-700">Subject</label><input type="text" name="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></div>
        <div><label class="block text-sm font-medium text-gray-700">Message</label><textarea name="message" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"></textarea></div>
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">Send Message</button>
    </form>
</div>
@endsection
