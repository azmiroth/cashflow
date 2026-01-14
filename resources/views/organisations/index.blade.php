@extends('layouts.app')

@section('title', 'Organisations')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Organisations</h1>
            <p class="text-gray-600 mt-1">Manage your organisations</p>
        </div>
        <a href="{{ route('organisations.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
            + Create Organisation
        </a>
    </div>

    @if($organisations->isEmpty())
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-5xl mb-4">📁</div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">No Organisations Yet</h2>
        <p class="text-gray-600 mb-6">Create your first organisation to start tracking cash flow</p>
        <a href="{{ route('organisations.create') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
            Create Your First Organisation
        </a>
    </div>
    @else
    <!-- Organisations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($organisations as $organisation)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
            <!-- Organisation Card Header -->
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ $organisation->name }}</h3>
                <p class="text-gray-600 text-sm mt-1">{{ $organisation->description ?? 'No description' }}</p>
            </div>

            <!-- Organisation Stats -->
            <div class="p-6 space-y-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Balance:</span>
                    <span class="font-semibold text-gray-900">{{ $organisation->currency ?? 'AUD' }} {{ number_format($organisation->getTotalBalance(), 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Accounts:</span>
                    <span class="font-semibold text-gray-900">{{ $organisation->getAccountCount() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Status:</span>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $organisation->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $organisation->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-6 flex gap-3">
                <a href="{{ route('organisations.show', $organisation->id) }}" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center font-medium text-sm">
                    View
                </a>
                <a href="{{ route('organisations.edit', $organisation->id) }}" class="flex-1 bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 text-center font-medium text-sm">
                    Edit
                </a>
                <form method="POST" action="{{ route('organisations.destroy', $organisation->id) }}" class="flex-1" onsubmit="return confirm('Are you sure?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-medium text-sm">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
