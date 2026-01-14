@extends('layouts.app')

@section('title', 'Import Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $import->filename }}</h1>
        <p class="text-gray-600 mt-1">{{ $import->bankAccount->account_name }} - {{ $import->bankAccount->bank_name }}</p>
    </div>

    <!-- Import Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Total Records -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Total Records</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $import->total_records }}</p>
        </div>

        <!-- Successful -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Successful</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $import->successful_records }}</p>
            <p class="text-xs text-gray-600 mt-2">{{ round(($import->successful_records / max($import->total_records, 1)) * 100) }}%</p>
        </div>

        <!-- Failed -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Failed</p>
            <p class="text-3xl font-bold {{ $import->failed_records > 0 ? 'text-red-600' : 'text-gray-600' }} mt-2">{{ $import->failed_records }}</p>
            <p class="text-xs text-gray-600 mt-2">{{ round(($import->failed_records / max($import->total_records, 1)) * 100) }}%</p>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Status</p>
            <p class="mt-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                    {{ $import->status === 'completed' ? 'bg-green-100 text-green-800' : 
                       ($import->status === 'completed_with_errors' ? 'bg-yellow-100 text-yellow-800' : 
                        ($import->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                    {{ ucfirst(str_replace('_', ' ', $import->status)) }}
                </span>
            </p>
        </div>

        <!-- Date -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Imported</p>
            <p class="text-sm font-semibold text-gray-900 mt-2">{{ $import->created_at->format('M d, Y') }}</p>
            <p class="text-xs text-gray-600 mt-1">{{ $import->created_at->format('H:i:s') }}</p>
        </div>
    </div>

    <!-- Import Details -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Import Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm">Bank Account</p>
                <p class="text-lg font-semibold text-gray-900">{{ $import->bankAccount->account_name }}</p>
                <p class="text-sm text-gray-600">{{ $import->bankAccount->account_number }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Imported By</p>
                <p class="text-lg font-semibold text-gray-900">{{ $import->importedBy->name }}</p>
                <p class="text-sm text-gray-600">{{ $import->importedBy->email }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">File</p>
                <p class="text-lg font-semibold text-gray-900">{{ $import->filename }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">File Path</p>
                <p class="text-sm text-gray-600 break-all">{{ $import->file_path }}</p>
            </div>
        </div>

        @if($import->error_message)
        <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-900 font-semibold mb-1">Error Message</p>
            <p class="text-red-800 text-sm">{{ $import->error_message }}</p>
        </div>
        @endif
    </div>

    <!-- Success Rate Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Import Summary</h2>
        <div class="space-y-4">
            <!-- Success Rate -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-700 font-medium">Success Rate</span>
                    <span class="text-gray-900 font-semibold">{{ round(($import->successful_records / max($import->total_records, 1)) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-green-600 h-3 rounded-full" style="width: {{ ($import->successful_records / max($import->total_records, 1)) * 100 }}%"></div>
                </div>
            </div>

            <!-- Records Breakdown -->
            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $import->successful_records }}</p>
                    <p class="text-sm text-gray-600">Imported</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $import->failed_records }}</p>
                    <p class="text-sm text-gray-600">Failed</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $import->total_records }}</p>
                    <p class="text-sm text-gray-600">Total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Links -->
    <div class="flex gap-4">
        <a href="{{ route('imports.index', $organisation->id) }}" class="text-blue-600 hover:underline">
            ← Back to Import History
        </a>
        <a href="{{ route('bank-accounts.show', [$organisation->id, $import->bank_account_id]) }}" class="text-blue-600 hover:underline">
            View Bank Account →
        </a>
    </div>
</div>
@endsection
