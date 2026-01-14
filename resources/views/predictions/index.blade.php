@extends('layouts.app')

@section('title', 'Cash Flow Predictions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Cash Flow Predictions</h1>
            <p class="text-gray-600 mt-1">{{ $organisation->name }}</p>
        </div>
        <a href="{{ route('predictions.create', $organisation->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Create Prediction
        </a>
    </div>

    <!-- Predictions List -->
    @if($predictions->isEmpty())
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <p class="text-gray-600 mb-4">No predictions yet.</p>
        <a href="{{ route('predictions.create', $organisation->id) }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Create Your First Prediction
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($predictions as $prediction)
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $prediction->prediction_name }}</h3>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ ucfirst(str_replace('_', ' ', $prediction->prediction_method)) }}
                </span>
            </div>

            <div class="space-y-3 mb-4">
                <!-- Predicted Balance -->
                <div>
                    <p class="text-gray-600 text-sm">Predicted Balance</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $organisation->currency }} {{ number_format($prediction->predicted_balance, 2) }}
                    </p>
                </div>

                <!-- Confidence -->
                <div>
                    <p class="text-gray-600 text-sm">Confidence Level</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $prediction->confidence_level }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ round($prediction->confidence_level) }}%</span>
                    </div>
                </div>

                <!-- Trend -->
                <div>
                    <p class="text-gray-600 text-sm">Trend</p>
                    <p class="text-sm font-semibold">
                        <span class="px-3 py-1 rounded-full 
                            {{ $prediction->trend === 'increasing' ? 'bg-green-100 text-green-800' : 
                               ($prediction->trend === 'decreasing' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($prediction->trend) }}
                        </span>
                    </p>
                </div>

                <!-- Analysis Period -->
                <div class="text-xs text-gray-600">
                    <p>Analysis: {{ $prediction->analysis_period_days }} days | Forecast: {{ $prediction->forecast_period_days }} days</p>
                </div>
            </div>

            <!-- Created Info -->
            <div class="text-xs text-gray-500 mb-4 pb-4 border-b border-gray-200">
                Created {{ $prediction->created_at->diffForHumans() }}
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <a href="{{ route('predictions.show', [$organisation->id, $prediction->id]) }}" class="flex-1 text-center bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                    View Details
                </a>
                <form method="POST" action="{{ route('predictions.destroy', [$organisation->id, $prediction->id]) }}" class="flex-1" onsubmit="return confirm('Delete this prediction?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $predictions->links() }}
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
