@extends('layouts.app')

@section('title', 'Import History')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Import History</h1>
            <p class="text-gray-600 mt-1">{{ $organisation->name }}</p>
        </div>
        @if($bankAccounts->count() > 0)
        <a href="{{ route('imports.create', [$organisation->id, $bankAccounts->first()->id]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Import CSV
        </a>
        @endif
    </div>

    <!-- Import History Table -->
    @if($imports->isEmpty())
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <p class="text-gray-600 mb-4">No imports yet.</p>
        <a href="{{ route('bank-accounts.index', $organisation->id) }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Go to Bank Accounts
        </a>
    </div>
    @else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Filename</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Bank Account</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Imported By</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Total Records</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Successful</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Failed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($imports as $import)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $import->filename }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $import->bankAccount->account_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $import->importedBy->name }}</td>
                        <td class="px-6 py-4 text-sm text-center text-gray-900">{{ $import->total_records }}</td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $import->successful_records }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            @if($import->failed_records > 0)
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $import->failed_records }}
                            </span>
                            @else
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                0
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                {{ $import->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($import->status === 'completed_with_errors' ? 'bg-yellow-100 text-yellow-800' : 
                                    ($import->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ ucfirst(str_replace('_', ' ', $import->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $import->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('imports.show', [$organisation->id, $import->id]) }}" class="text-blue-600 hover:underline">Details</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $imports->links() }}
    </div>
    @endif

    <!-- Back Link -->
    <div>
        <a href="{{ route('organisations.show', $organisation->id) }}" class="text-blue-600 hover:underline">
            ← Back to Organisation
        </a>
    </div>
</div>
@endsection
