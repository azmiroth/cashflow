<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $monthlyInflows = [];
        $monthlyOutflows = [];
        $monthlyBalances = [];
        $totalInflows = 0;
        $totalOutflows = 0;

        $current = $startDate->copy();
        $runningBalance = 0;

        // Calculate opening balance for all accounts
        foreach ($bankAccounts as $account) {
            $runningBalance += $account->opening_balance;
        }

        while ($current <= $endDate) {
            $monthKey = $current->format('M Y');
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthFlow = 0;
            $inflows = 0;
            $outflows = 0;
            
            foreach ($bankAccounts as $account) {
                $transactions = $account->transactions()
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->where('excluded_from_analysis', false)
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->type === 'credit') {
                        $monthFlow += $transaction->amount;
                        $inflows += $transaction->amount;
                        $totalInflows += $transaction->amount;
                    } else {
                        $monthFlow -= $transaction->amount;
                        $outflows += $transaction->amount;
                        $totalOutflows += $transaction->amount;
                    }
                }
            }

            // Update running balance
            $runningBalance += $monthFlow;

            $monthlyData[$monthKey] = $monthFlow;
            $monthlyInflows[$monthKey] = $inflows;
            $monthlyOutflows[$monthKey] = $outflows;
            $monthlyBalances[$monthKey] = $runningBalance;

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

        return view('dashboard.analytics', [
            'organisation' => $organisation,
            'bankAccounts' => $bankAccounts,
            'monthlyData' => $monthlyData,
            'monthlyInflows' => $monthlyInflows,
            'monthlyOutflows' => $monthlyOutflows,
            'monthlyBalances' => $monthlyBalances,
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
