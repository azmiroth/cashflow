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

    <!-- Monthly Net Cash Flow Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Net Cash Flow</h2>
        <canvas id="monthlyCashFlowChart" height="80"></canvas>
    </div>

    <!-- Monthly Inflows & Outflows Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Inflows vs Outflows</h2>
        <canvas id="inflowsOutflowsChart" height="80"></canvas>
    </div>

    <!-- Month-Ending Balance Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Month-Ending Balance</h2>
        <canvas id="balanceChart" height="80"></canvas>
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
    // Helper function to sort months chronologically
    function getSortedMonths(monthlyData) {
        const monthOrder = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return Object.keys(monthlyData).sort((a, b) => {
            const [monthA, yearA] = a.split(' ');
            const [monthB, yearB] = b.split(' ');
            
            if (yearA !== yearB) {
                return parseInt(yearA) - parseInt(yearB);
            }
            return monthOrder.indexOf(monthA) - monthOrder.indexOf(monthB);
        });
    }

    // Monthly Net Cash Flow Chart
    const ctx1 = document.getElementById('monthlyCashFlowChart').getContext('2d');
    const monthlyData = @json($monthlyData);
    const sortedMonths = getSortedMonths(monthlyData);
    const netFlowData = sortedMonths.map(month => monthlyData[month]);
    
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: sortedMonths,
            datasets: [
                {
                    label: 'Net Cash Flow',
                    data: netFlowData,
                    backgroundColor: netFlowData.map(value => value >= 0 ? '#10b981' : '#ef4444'),
                    borderColor: netFlowData.map(value => value >= 0 ? '#059669' : '#dc2626'),
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

    // Monthly Inflows vs Outflows Chart
    const ctx2 = document.getElementById('inflowsOutflowsChart').getContext('2d');
    const monthlyInflows = @json($monthlyInflows);
    const monthlyOutflows = @json($monthlyOutflows);
    
    const inflowsData = sortedMonths.map(month => monthlyInflows[month] || 0);
    const outflowsData = sortedMonths.map(month => monthlyOutflows[month] || 0);
    
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: sortedMonths,
            datasets: [
                {
                    label: 'Inflows',
                    data: inflowsData,
                    backgroundColor: '#10b981',
                    borderColor: '#059669',
                    borderWidth: 1,
                },
                {
                    label: 'Outflows',
                    data: outflowsData,
                    backgroundColor: '#ef4444',
                    borderColor: '#dc2626',
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

    // Month-Ending Balance Chart
    const ctx3 = document.getElementById('balanceChart').getContext('2d');
    const monthlyBalances = @json($monthlyBalances);
    
    const balanceData = sortedMonths.map(month => monthlyBalances[month] || 0);
    
    new Chart(ctx3, {
        type: 'line',
        data: {
            labels: sortedMonths,
            datasets: [
                {
                    label: 'Month-Ending Balance',
                    data: balanceData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#1e40af',
                    pointRadius: 5,
                    pointHoverRadius: 7,
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
