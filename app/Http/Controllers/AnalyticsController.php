<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show cash flow analytics dashboard
     */
    public function index(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $bankAccounts = $organisation->bankAccounts()->where('is_active', true)->get();

        // Get last 12 months of data
        $startDate = now()->subMonths(12)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Calculate monthly cash flows (excluding marked transactions)
        $monthlyData = [];
        $totalInflows = 0;
        $totalOutflows = 0;

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $monthKey = $current->format('M Y');
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthFlow = 0;
            $monthInflows = 0;
            $monthOutflows = 0;
            
            foreach ($bankAccounts as $account) {
                $transactions = $account->transactions()
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->where('excluded_from_analysis', false)
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->type === 'credit') {
                        $monthFlow += $transaction->amount;
                        $monthInflows += $transaction->amount;
                        $totalInflows += $transaction->amount;
                    } else {
                        $monthFlow -= $transaction->amount;
                        $monthOutflows += $transaction->amount;
                        $totalOutflows += $transaction->amount;
                    }
                }
            }

            // Debug logging for January
            if ($monthKey === 'Jan 26' || $monthKey === 'Jan 2026') {
                Log::info("Analytics Debug - $monthKey", [
                    'month_inflows' => $monthInflows,
                    'month_outflows' => $monthOutflows,
                    'month_flow' => $monthFlow,
                    'transaction_count' => count($transactions ?? [])
                ]);
            }

            $monthlyData[$monthKey] = $monthFlow;
            $current->addMonth();
        }

        // Calculate summary stats
        $netCashFlow = $totalInflows - $totalOutflows;
        $monthCount = count(array_filter($monthlyData, fn($v) => $v != 0));
        $averageMonthlyFlow = $netCashFlow / 12;

        // Count excluded transactions
        $excludedTransactionCount = DB::table('transactions')
            ->whereIn('bank_account_id', $bankAccounts->pluck('id'))
            ->where('excluded_from_analysis', true)
            ->count();

        // Debug logging for totals
        Log::info("Analytics Debug - Totals", [
            'total_inflows' => $totalInflows,
            'total_outflows' => $totalOutflows,
            'net_cash_flow' => $netCashFlow,
            'monthly_data' => $monthlyData
        ]);

        return view('dashboard.analytics', [
            'organisation' => $organisation,
            'bankAccounts' => $bankAccounts,
            'monthlyData' => $monthlyData,
            'totalInflows' => $totalInflows,
            'totalOutflows' => $totalOutflows,
            'netCashFlow' => $netCashFlow,
            'averageMonthlyFlow' => $averageMonthlyFlow,
            'excludedTransactionCount' => $excludedTransactionCount,
        ]);
    }

    /**
     * Helper method for authorization
     */
    private function authorizeOrganisation($organisation)
    {
        $user = Auth::user();
        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            abort(403);
        }
    }
}
