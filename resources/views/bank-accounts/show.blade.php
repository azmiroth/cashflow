@extends('layouts.app')

@section('title', $bankAccount->account_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $bankAccount->account_name }}</h1>
            <p class="text-gray-600 mt-1">{{ $bankAccount->bank_name }} - {{ ucfirst($bankAccount->account_type) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('bank-accounts.edit', [$organisation->id, $bankAccount->id]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('imports.create', [$organisation->id, $bankAccount->id]) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                Import CSV
            </a>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Calculated Balance -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow p-6 text-white">
            <p class="text-blue-100 text-sm font-medium">Calculated Balance (Today)</p>
            <p class="text-3xl font-bold mt-2">{{ $bankAccount->currency }} {{ number_format($calculatedBalance, 2) }}</p>
            <p class="text-blue-100 text-xs mt-4">Opening: {{ $bankAccount->currency }} {{ number_format($bankAccount->opening_balance, 2) }}</p>
        </div>

        <!-- Latest CSV Import Balance -->
        <div class="{{ $latestImportBalance ? 'bg-gradient-to-r from-green-600 to-green-800' : 'bg-gradient-to-r from-gray-600 to-gray-800' }} rounded-lg shadow p-6 text-white">
            <p class="text-green-100 text-sm font-medium">Latest CSV Import Balance</p>
            @if($latestImportBalance)
                <p class="text-3xl font-bold mt-2">{{ $bankAccount->currency }} {{ number_format($latestImportBalance, 2) }}</p>
            @else
                <p class="text-3xl font-bold mt-2 text-gray-300">No imports yet</p>
            @endif
        </div>

        <!-- Match Status -->
        <div class="{{ $balancesMatch ? 'bg-gradient-to-r from-emerald-600 to-emerald-800' : 'bg-gradient-to-r from-amber-600 to-amber-800' }} rounded-lg shadow p-6 text-white">
            <p class="text-sm font-medium">Reconciliation Status</p>
            @if($latestImportBalance)
                <p class="text-3xl font-bold mt-2">{{ $balancesMatch ? '✓ Match' : '✗ Mismatch' }}</p>
                <p class="text-xs mt-4">Difference: {{ $bankAccount->currency }} {{ number_format(abs($calculatedBalance - $latestImportBalance), 2) }}</p>
            @else
                <p class="text-3xl font-bold mt-2 text-gray-300">-</p>
            @endif
        </div>
    </div>

    <!-- Account Details -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Account Type</p>
            <p class="text-lg font-semibold text-gray-900 mt-2">{{ ucfirst($bankAccount->account_type) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Currency</p>
            <p class="text-lg font-semibold text-gray-900 mt-2">{{ $bankAccount->currency }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Opening Balance</p>
            <p class="text-lg font-semibold text-gray-900 mt-2">{{ $bankAccount->currency }} {{ number_format($bankAccount->opening_balance, 2) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Status</p>
            <p class="text-lg font-semibold mt-2">
                <span class="px-3 py-1 rounded-full text-sm {{ $bankAccount->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $bankAccount->is_active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        </div>

        @if($transactions->isEmpty())
        <div class="px-6 py-8 text-center">
            <p class="text-gray-600">No transactions yet.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $transaction->type === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right font-medium {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $transaction->reference ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

    <!-- Back Link -->
    <div>
        <a href="{{ route('bank-accounts.index', $organisation->id) }}" class="text-blue-600 hover:underline">
            ← Back to Bank Accounts
        </a>
    </div>
</div>
@endsection
