@extends('layouts.app')

@section('title', 'Edit Bank Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Edit Bank Account</h1>

        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('bank-accounts.update', [$organisation->id, $bankAccount->id]) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Account Name -->
            <div>
                <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                <input 
                    type="text" 
                    id="account_name" 
                    name="account_name" 
                    value="{{ old('account_name', $bankAccount->account_name) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('account_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- BSB Number -->
            <div>
                <label for="bsb_number" class="block text-sm font-medium text-gray-700 mb-2">BSB Number</label>
                <input 
                    type="text" 
                    id="bsb_number" 
                    name="bsb_number" 
                    value="{{ old('bsb_number', $bankAccount->bsb_number ?? '') }}"
                    maxlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="text-gray-600 text-xs mt-1">Format: XXX-XXX</p>
                @error('bsb_number')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Account Number -->
            <div>
                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                <input 
                    type="text" 
                    id="account_number" 
                    name="account_number" 
                    value="{{ old('account_number', $bankAccount->account_number) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('account_number')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bank Name -->
            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                <input 
                    type="text" 
                    id="bank_name" 
                    name="bank_name" 
                    value="{{ old('bank_name', $bankAccount->bank_name) }}"
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
                    <option value="checking" {{ old('account_type', $bankAccount->account_type) == 'checking' ? 'selected' : '' }}>Checking</option>
                    <option value="savings" {{ old('account_type', $bankAccount->account_type) == 'savings' ? 'selected' : '' }}>Savings</option>
                    <option value="credit" {{ old('account_type', $bankAccount->account_type) == 'credit' ? 'selected' : '' }}>Credit Card</option>
                    <option value="investment" {{ old('account_type', $bankAccount->account_type) == 'investment' ? 'selected' : '' }}>Investment</option>
                    <option value="other" {{ old('account_type', $bankAccount->account_type) == 'other' ? 'selected' : '' }}>Other</option>
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
                    value="{{ old('currency', $bankAccount->currency) }}"
                    maxlength="3"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('currency')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div>
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1"
                        {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    Update
                </button>
                <a 
                    href="{{ route('bank-accounts.show', [$organisation->id, $bankAccount->id]) }}" 
                    class="flex-1 bg-gray-200 text-gray-900 font-semibold py-2 rounded-lg hover:bg-gray-300 transition text-center"
                >
                    Cancel
                </a>
            </div>
        </form>

        <!-- Delete Button -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <form method="POST" action="{{ route('bank-accounts.destroy', [$organisation->id, $bankAccount->id]) }}" onsubmit="return confirm('Are you sure? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-600 text-white font-semibold py-2 rounded-lg hover:bg-red-700 transition">
                    Delete Account
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
