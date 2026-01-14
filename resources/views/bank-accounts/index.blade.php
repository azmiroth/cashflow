@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Bank Accounts</h1>
        <a href="{{ route('bank-accounts.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Add Account
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4">
        @forelse ($bankAccounts as $account)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $account->account_name }}</h3>
                    <p class="text-gray-600 text-sm">{{ $account->bank_name }} - {{ $account->account_number }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $account->currency }} {{ number_format($account->current_balance, 2) }}</p>
                </div>
                <div class="space-x-2">
                    <a href="{{ route('imports.create', $account) }}" class="px-3 py-1 text-green-600 hover:bg-green-50 rounded">Import</a>
                    <a href="{{ route('imports.history', $account) }}" class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded">History</a>
                    <a href="{{ route('bank-accounts.edit', $account) }}" class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded">Edit</a>
                    <form method="POST" action="{{ route('bank-accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <p class="text-gray-600">No bank accounts yet. <a href="{{ route('bank-accounts.create') }}" class="text-blue-600 hover:underline">Add one</a></p>
        @endforelse
    </div>
</div>
@endsection
