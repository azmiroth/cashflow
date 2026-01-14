@extends('layouts.app')

@section('title', $organisation->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $organisation->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $organisation->description }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('organisations.edit', $organisation->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <form method="POST" action="{{ route('organisations.destroy', $organisation->id) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Total Balance</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                {{ $organisation->currency }} {{ number_format($totalBalance, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Bank Accounts</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $bankAccounts->count() }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Transactions</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $transactionCount }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Predictions</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $predictionCount }}</p>
        </div>
    </div>

    <!-- Bank Accounts Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Bank Accounts</h2>
            <a href="{{ route('bank-accounts.create', $organisation->id) }}" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                + Add Account
            </a>
        </div>

        @if($bankAccounts->isEmpty())
        <div class="px-6 py-8 text-center">
            <p class="text-gray-600">No bank accounts yet. <a href="{{ route('bank-accounts.create', $organisation->id) }}" class="text-blue-600 hover:underline">Create one</a></p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Bank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankAccounts as $account)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $account->account_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $account->bank_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($account->account_type) }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">
                            {{ $account->currency }} {{ number_format($account->current_balance, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('bank-accounts.show', [$organisation->id, $account->id]) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('bank-accounts.edit', [$organisation->id, $account->id]) }}" class="text-blue-600 hover:underline ml-3">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('imports.index', $organisation->id) }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Import Statements</h3>
            <p class="text-gray-600 text-sm">Import bank statements via CSV</p>
        </a>

        <a href="{{ route('predictions.index', $organisation->id) }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Predictions</h3>
            <p class="text-gray-600 text-sm">View cash flow predictions</p>
        </a>

        <a href="{{ route('organisations.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Back to Organisations</h3>
            <p class="text-gray-600 text-sm">View all organisations</p>
        </a>
    </div>
</div>
@endsection
