@extends('layouts.app')

@section('title', $prediction->prediction_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $prediction->prediction_name }}</h1>
            <p class="text-gray-600 mt-1">Created {{ $prediction->created_at->diffForHumans() }}</p>
        </div>
        <form method="POST" action="{{ route('predictions.destroy', [$organisation->id, $prediction->id]) }}" class="inline" onsubmit="return confirm('Delete this prediction?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                Delete
            </button>
        </form>
    </div>

    <!-- Main Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Predicted Balance -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow p-6 text-white">
            <p class="text-blue-100 text-sm font-medium">Predicted Balance</p>
            <p class="text-4xl font-bold mt-2">
                {{ $organisation->currency }} {{ number_format($prediction->predicted_balance, 2) }}
            </p>
            <p class="text-blue-100 text-sm mt-4">After {{ $prediction->forecast_period_days }} days</p>
        </div>

        <!-- Confidence Level -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Confidence Level</p>
            <p class="text-4xl font-bold text-gray-900 mt-2">{{ round($prediction->confidence_level) }}%</p>
            <div class="mt-4 bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $prediction->confidence_level }}%"></div>
            </div>
        </div>

        <!-- Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Trend</p>
            <p class="text-2xl font-bold mt-2">
                <span class="px-4 py-2 rounded-full 
                    {{ $prediction->trend === 'increasing' ? 'bg-green-100 text-green-800' : 
                       ($prediction->trend === 'decreasing' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ ucfirst($prediction->trend) }}
                </span>
            </p>
        </div>
    </div>

    <!-- Prediction Details -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Prediction Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm">Method</p>
                <p class="text-lg font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $prediction->prediction_method)) }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Analysis Period</p>
                <p class="text-lg font-semibold text-gray-900">{{ $prediction->analysis_period_days }} days</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Forecast Period</p>
                <p class="text-lg font-semibold text-gray-900">{{ $prediction->forecast_period_days }} days</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Created By</p>
                <p class="text-lg font-semibold text-gray-900">{{ $prediction->createdBy->name }}</p>
            </div>
        </div>
    </div>

    <!-- Bank Accounts Used -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Bank Accounts Used</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Bank</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Current Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accountSelections as $selection)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $selection->bankAccount->account_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $selection->bankAccount->bank_name }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">
                            {{ $selection->bankAccount->currency }} {{ number_format($selection->bankAccount->current_balance, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Method Explanation -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">How This Prediction Works</h3>
        @if($prediction->prediction_method === 'moving_average')
        <div class="text-blue-800 space-y-2">
            <p><strong>Method:</strong> Moving Average</p>
            <p>This prediction calculates the average daily cash flow over the past {{ $prediction->analysis_period_days }} days and projects it forward {{ $prediction->forecast_period_days }} days.</p>
            <p><strong>Formula:</strong> Predicted Balance = Current Balance + (Average Daily Flow × {{ $prediction->forecast_period_days }} days)</p>
            <p><strong>Confidence:</strong> Based on the consistency of daily flows. Higher confidence indicates more stable, predictable cash flow.</p>
        </div>
        @else
        <div class="text-blue-800 space-y-2">
            <p><strong>Method:</strong> Trend Analysis</p>
            <p>This prediction uses linear regression to identify trends in your cash flow over the past {{ $prediction->analysis_period_days }} days and projects those trends forward {{ $prediction->forecast_period_days }} days.</p>
            <p><strong>Formula:</strong> Uses linear regression (y = mx + b) to fit historical data and project future values.</p>
            <p><strong>Confidence:</strong> R-squared value indicating how well the linear model fits the historical data. Higher values indicate stronger trends.</p>
        </div>
        @endif
    </div>

    <!-- Back Link -->
    <div>
        <a href="{{ route('predictions.index', $organisation->id) }}" class="text-blue-600 hover:underline">
            ← Back to Predictions
        </a>
    </div>
</div>
@endsection
