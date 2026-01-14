@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome back, {{ Auth::user()->name }}!</p>
        </div>
    </div>

    @if($organisations->isEmpty())
    <!-- No Organisations Message -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-blue-900 mb-2">Get Started</h2>
        <p class="text-blue-700 mb-4">You don't have any organisations yet. Create one to start tracking your cash flow.</p>
        <a href="{{ route('organisations.create') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Create Organisation
        </a>
    </div>
    @else
    <!-- Organisation Selector -->
    <div class="bg-white rounded-lg shadow p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Organisation:</label>
        <form method="POST" action="{{ route('dashboard.switch') }}" class="flex gap-2">
            @csrf
            <select name="organisation_id" onchange="this.form.submit()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @foreach($organisations as $org)
                <option value="{{ $org->id }}" {{ $organisation->id == $org->id ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Organisation Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Total Balance -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $organisation->currency ?? 'AUD' }} {{ number_format($totalBalance, 2) }}
                    </p>
                </div>
                <div class="text-3xl">💰</div>
            </div>
        </div>

        <!-- Bank Accounts -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Bank Accounts</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $bankAccounts->count() }}
                    </p>
                </div>
                <div class="text-3xl">🏦</div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $recentTransactions->count() }}
                    </p>
                </div>
                <div class="text-3xl">📊</div>
            </div>
        </div>

        <!-- Predictions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Predictions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $predictions->count() }}
                    </p>
                </div>
                <div class="text-3xl">📈</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('bank-accounts.index', $organisation->id) }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Bank Accounts</h3>
            <p class="text-gray-600 text-sm">Manage your bank accounts</p>
        </a>

        <a href="{{ route('import.index', $organisation->id) }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Import Statements</h3>
            <p class="text-gray-600 text-sm">Import bank statements via CSV</p>
        </a>

        <a href="{{ route('predictions.index', $organisation->id) }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Predictions</h3>
            <p class="text-gray-600 text-sm">View cash flow predictions</p>
        </a>
    </div>

    <!-- Recent Transactions -->
    @if($recentTransactions->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $transaction)
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection
