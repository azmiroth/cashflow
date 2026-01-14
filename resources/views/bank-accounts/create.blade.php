@extends('layouts.app')

@section('title', 'Add Bank Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Add Bank Account</h1>

        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('bank-accounts.store', $organisation->id) }}" class="space-y-6">
            @csrf

            <!-- Account Name -->
            <div>
                <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                <input 
                    type="text" 
                    id="account_name" 
                    name="account_name" 
                    value="{{ old('account_name') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="My Checking Account"
                >
                @error('account_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- BSB Number -->
            <div>
                <label for="bsb_number" class="block text-sm font-medium text-gray-700 mb-2">BSB Number *</label>
                <input 
                    type="text" 
                    id="bsb_number" 
                    name="bsb_number" 
                    value="{{ old('bsb_number') }}"
                    required
                    maxlength="6"
                    placeholder="062-000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="text-gray-600 text-xs mt-1">Format: XXX-XXX (e.g., 062-000 for Commonwealth Bank)</p>
                @error('bsb_number')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Account Number -->
            <div>
                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number *</label>
                <input 
                    type="text" 
                    id="account_number" 
                    name="account_number" 
                    value="{{ old('account_number') }}"
                    required
                    placeholder="123456789"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="text-gray-600 text-xs mt-1">Usually 8 digits</p>
                @error('account_number')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bank Name -->
            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name *</label>
                <input 
                    type="text" 
                    id="bank_name" 
                    name="bank_name" 
                    value="{{ old('bank_name') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Commonwealth Bank of Australia"
                >
                <p class="text-gray-600 text-xs mt-1">e.g., Commonwealth Bank, Westpac, NAB, ANZ</p>
                @error('bank_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Account Type -->
            <div>
                <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                <select 
                    id="account_type" 
                    name="account_type" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select account type</option>
                    <option value="checking" {{ old('account_type') == 'checking' ? 'selected' : '' }}>Checking</option>
                    <option value="savings" {{ old('account_type') == 'savings' ? 'selected' : '' }}>Savings</option>
                    <option value="credit" {{ old('account_type') == 'credit' ? 'selected' : '' }}>Credit Card</option>
                    <option value="investment" {{ old('account_type') == 'investment' ? 'selected' : '' }}>Investment</option>
                    <option value="other" {{ old('account_type') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('account_type')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Currency -->
            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                <input 
                    type="text" 
                    id="currency" 
                    name="currency" 
                    value="{{ old('currency', $organisation->currency) }}"
                    maxlength="3"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('currency')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Opening Balance and Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-gray-700 mb-2">Opening Balance</label>
                    <input 
                        type="number" 
                        id="opening_balance" 
                        name="opening_balance" 
                        value="{{ old('opening_balance', 0) }}"
                        step="0.01"
                        min="0"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0.00"
                    >
                    @error('opening_balance')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="opening_balance_date" class="block text-sm font-medium text-gray-700 mb-2">Opening Balance As At</label>
                    <input 
                        type="date" 
                        id="opening_balance_date" 
                        name="opening_balance_date" 
                        value="{{ old('opening_balance_date', date('Y-m-d')) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('opening_balance_date')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    Create Account
                </button>
                <a 
                    href="{{ route('bank-accounts.index', $organisation->id) }}" 
                    class="flex-1 bg-gray-200 text-gray-900 font-semibold py-2 rounded-lg hover:bg-gray-300 transition text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
