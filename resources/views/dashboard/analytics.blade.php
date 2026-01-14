@extends('layouts.app')

@section('title', 'Cash Flow Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Cash Flow Analytics</h1>
            <p class="text-gray-600 mt-1">{{ $organisation->name }} - Last 12 Months</p>
        </div>
    </div>

    <!-- Monthly Cash Flow Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Net Cash Flow</h2>
        <canvas id="monthlyCashFlowChart" height="80"></canvas>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Total Inflows (12m)</p>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ $organisation->currency }} {{ number_format($totalInflows, 2) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Total Outflows (12m)</p>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ $organisation->currency }} {{ number_format($totalOutflows, 2) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Net Cash Flow (12m)</p>
            <p class="text-2xl font-bold {{ $netCashFlow >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                {{ $organisation->currency }} {{ number_format($netCashFlow, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Average Monthly Flow</p>
            <p class="text-2xl font-bold {{ $averageMonthlyFlow >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                {{ $organisation->currency }} {{ number_format($averageMonthlyFlow, 2) }}
            </p>
        </div>
    </div>

    <!-- Excluded Transactions Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <strong>Note:</strong> This analysis excludes {{ $excludedTransactionCount }} transaction(s) marked as excluded from analysis.
            <a href="{{ route('bank-accounts.show', [$organisation->id, $bankAccounts->first()->id ?? 0]) }}" class="text-blue-600 hover:underline">
                Manage exclusions
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyCashFlowChart').getContext('2d');
    const monthlyData = @json($monthlyData);
    
    // Sort labels chronologically (oldest to newest)
    const monthOrder = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const labels = Object.keys(monthlyData).sort((a, b) => {
        const [monthA, yearA] = a.split(' ');
        const [monthB, yearB] = b.split(' ');
        
        if (yearA !== yearB) {
            return parseInt(yearA) - parseInt(yearB);
        }
        return monthOrder.indexOf(monthA) - monthOrder.indexOf(monthB);
    });
    const data = labels.map(month => monthlyData[month]);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Net Cash Flow',
                    data: data,
                    backgroundColor: data.map(value => value >= 0 ? '#10b981' : '#ef4444'),
                    borderColor: data.map(value => value >= 0 ? '#059669' : '#dc2626'),
                    borderWidth: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                title: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '{{ $organisation->currency }} ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
