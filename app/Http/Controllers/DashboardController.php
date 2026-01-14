<?php
/**
 * Dashboard Controller
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $organisations = $user->organisations()->with('bankAccounts')->get();

        // Get current organisation from session or first organisation
        $orgId = session('current_organisation_id');
        if (!$orgId && $organisations->isNotEmpty()) {
            $orgId = $organisations->first()->id;
            session(['current_organisation_id' => $orgId]);
        }

        if (!$orgId) {
            return redirect()->route('organisations.index');
        }

        $organisation = Organisation::findOrFail($orgId);

        // Check access
        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $orgId)->exists()) {
            abort(403);
        }

        $bankAccounts = $organisation->bankAccounts()->where('is_active', true)->get();
        
        // Calculate total balance from all active accounts
        $totalBalance = 0;
        foreach ($bankAccounts as $account) {
            $calculatedBalance = $account->opening_balance;
            $transactions = $account->transactions()->get();
            foreach ($transactions as $transaction) {
                if ($transaction->type === 'credit') {
                    $calculatedBalance += $transaction->amount;
                } else {
                    $calculatedBalance -= $transaction->amount;
                }
            }
            $totalBalance += $calculatedBalance;
        }

        $recentTransactions = $organisation->transactions()
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        $predictions = $organisation->predictions()
            ->latest()
            ->limit(5)
            ->get();

        // Get 30-day cash flow data
        $startDate = now()->subDays(30);
        $endDate = now();
        $dailyCashFlow = [];

        foreach ($bankAccounts as $account) {
            $flows = $account->getDailyCashFlow($startDate, $endDate);
            foreach ($flows as $flow) {
                $date = $flow->date;
                if (!isset($dailyCashFlow[$date])) {
                    $dailyCashFlow[$date] = 0;
                }
                $dailyCashFlow[$date] += $flow->net_flow;
            }
        }

        ksort($dailyCashFlow);

        return view('dashboard.index', [
            'organisation' => $organisation,
            'organisations' => $organisations,
            'bankAccounts' => $bankAccounts,
            'totalBalance' => $totalBalance,
            'recentTransactions' => $recentTransactions,
            'predictions' => $predictions,
            'dailyCashFlow' => $dailyCashFlow,
        ]);
    }

    /**
     * Switch organisation
     */
    public function switchOrganisation(Request $request)
    {
        $orgId = $request->input('organisation_id');
        $user = Auth::user();

        $organisation = Organisation::findOrFail($orgId);

        // Check access
        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $orgId)->exists()) {
            abort(403);
        }

        session(['current_organisation_id' => $orgId]);

        return redirect()->route('dashboard');
    }
}
