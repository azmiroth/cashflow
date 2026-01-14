@extends('layouts.app')

@section('title', 'Bank Accounts')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Bank Accounts</h1>
            <p class="text-gray-600 mt-1">{{ $organisation->name }}</p>
        </div>
        <a href="{{ route('bank-accounts.create', $organisation->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Add Account
        </a>
    </div>

    <!-- Total Balance Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow p-6 text-white">
        <p class="text-blue-100 text-sm font-medium">Total Balance</p>
        <p class="text-4xl font-bold mt-2">{{ $organisation->currency }} {{ number_format($totalBalance, 2) }}</p>
    </div>

    <!-- Bank Accounts Table -->
    @if($bankAccounts->isEmpty())
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <p class="text-gray-600 mb-4">No bank accounts yet.</p>
        <a href="{{ route('bank-accounts.create', $organisation->id) }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Create Your First Account
        </a>
    </div>
    @else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Bank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Account Number</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankAccounts as $account)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $account->account_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $account->bank_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($account->account_type) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $account->account_number }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">
                            {{ $account->currency }} {{ number_format($account->latest_balance, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="{{ route('bank-accounts.show', [$organisation->id, $account->id]) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('bank-accounts.edit', [$organisation->id, $account->id]) }}" class="text-blue-600 hover:underline">Edit</a>
                            <a href="{{ route('imports.create', [$organisation->id, $account->id]) }}" class="text-green-600 hover:underline">Import</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
