@extends('layouts.app')
@section('title', 'Create Organisation')
@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Create Organisation</h1>
    <form method="POST" action="{{ route('organisations.store') }}" class="bg-white rounded-lg shadow p-6">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" value="{{ old('name') }}">
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Currency</label>
                <input type="text" name="currency" required maxlength="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" value="{{ old('currency', 'USD') }}">
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Fiscal Year Start (MM-DD)</label>
                <input type="text" name="fiscal_year_start" required placeholder="01-01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" value="{{ old('fiscal_year_start', '01-01') }}">
            </div>
            <div class="flex space-x-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create</button>
                <a href="{{ route('organisations.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
