@extends('layouts.app')

@section('title', 'Create Prediction')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Create Cash Flow Prediction</h1>

        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('predictions.store', $organisation->id) }}" class="space-y-6">
            @csrf

            <!-- Prediction Name -->
            <div>
                <label for="prediction_name" class="block text-sm font-medium text-gray-700 mb-2">Prediction Name</label>
                <input 
                    type="text" 
                    id="prediction_name" 
                    name="prediction_name" 
                    value="{{ old('prediction_name') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Q1 2024 Forecast"
                >
                @error('prediction_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bank Accounts Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Accounts *</label>
                <div class="space-y-2 border border-gray-300 rounded-lg p-4">
                    @forelse($bankAccounts as $account)
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="bank_account_ids[]" 
                            value="{{ $account->id }}"
                            {{ in_array($account->id, old('bank_account_ids', [])) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">
                            {{ $account->account_name }} ({{ $account->currency }} {{ number_format($account->current_balance, 2) }})
                        </span>
                    </label>
                    @empty
                    <p class="text-gray-600 text-sm">No active bank accounts available. <a href="{{ route('bank-accounts.create', $organisation->id) }}" class="text-blue-600 hover:underline">Create one</a></p>
                    @endforelse
                </div>
                @error('bank_account_ids')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Analysis Period -->
            <div>
                <label for="analysis_period_days" class="block text-sm font-medium text-gray-700 mb-2">Analysis Period (Days) *</label>
                <select 
                    id="analysis_period_days" 
                    name="analysis_period_days" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select analysis period</option>
                    <option value="30" {{ old('analysis_period_days') == '30' ? 'selected' : '' }}>30 days (1 month)</option>
                    <option value="60" {{ old('analysis_period_days') == '60' ? 'selected' : '' }}>60 days (2 months)</option>
                    <option value="90" {{ old('analysis_period_days') == '90' ? 'selected' : '' }}>90 days (3 months)</option>
                    <option value="180" {{ old('analysis_period_days') == '180' ? 'selected' : '' }}>180 days (6 months)</option>
                    <option value="365" {{ old('analysis_period_days') == '365' ? 'selected' : '' }}>365 days (1 year)</option>
                </select>
                <p class="text-gray-600 text-xs mt-1">How far back to analyze transaction history</p>
                @error('analysis_period_days')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Forecast Period -->
            <div>
                <label for="forecast_period_days" class="block text-sm font-medium text-gray-700 mb-2">Forecast Period (Days) *</label>
                <select 
                    id="forecast_period_days" 
                    name="forecast_period_days" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select forecast period</option>
                    <option value="7" {{ old('forecast_period_days') == '7' ? 'selected' : '' }}>7 days (1 week)</option>
                    <option value="14" {{ old('forecast_period_days') == '14' ? 'selected' : '' }}>14 days (2 weeks)</option>
                    <option value="30" {{ old('forecast_period_days') == '30' ? 'selected' : '' }}>30 days (1 month)</option>
                    <option value="60" {{ old('forecast_period_days') == '60' ? 'selected' : '' }}>60 days (2 months)</option>
                    <option value="90" {{ old('forecast_period_days') == '90' ? 'selected' : '' }}>90 days (3 months)</option>
                </select>
                <p class="text-gray-600 text-xs mt-1">How far into the future to predict</p>
                @error('forecast_period_days')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Prediction Method -->
            <div>
                <label for="prediction_method" class="block text-sm font-medium text-gray-700 mb-2">Prediction Method *</label>
                <select 
                    id="prediction_method" 
                    name="prediction_method" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select method</option>
                    <option value="moving_average" {{ old('prediction_method') == 'moving_average' ? 'selected' : '' }}>Moving Average</option>
                    <option value="trend_analysis" {{ old('prediction_method') == 'trend_analysis' ? 'selected' : '' }}>Trend Analysis</option>
                </select>
                <p class="text-gray-600 text-xs mt-1">
                    <strong>Moving Average:</strong> Calculates average daily flow and projects it forward<br>
                    <strong>Trend Analysis:</strong> Uses linear regression to identify and project trends
                </p>
                @error('prediction_method')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition"
                >
                    Create Prediction
                </button>
                <a 
                    href="{{ route('predictions.index', $organisation->id) }}" 
                    class="flex-1 bg-gray-200 text-gray-900 font-semibold py-2 rounded-lg hover:bg-gray-300 transition text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
