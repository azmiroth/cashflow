<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of bank accounts
     */
    public function index(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $bankAccounts = $organisation->bankAccounts()->get();

        // Calculate latest balance for each account
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
            $account->latest_balance = $calculatedBalance;
            $totalBalance += $calculatedBalance;
        }

        return view('bank-accounts.index', [
            'organisation' => $organisation,
            'bankAccounts' => $bankAccounts,
            'totalBalance' => $totalBalance,
        ]);
    }

    /**
     * Show the form for creating a new bank account
     */
    public function create(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        return view('bank-accounts.create', [
            'organisation' => $organisation,
        ]);
    }

    /**
     * Store a newly created bank account
     */
    public function store(Request $request, Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:bank_accounts',
            'bank_name' => 'required|string|max:255',
            'bsb_number' => 'nullable|string|max:6',
            'account_type' => 'required|in:checking,savings,credit,investment,other',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'required|numeric|min:0',
            'opening_balance_date' => 'required|date',
        ]);

        $bankAccount = $organisation->bankAccounts()->create([
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'bank_name' => $validated['bank_name'],
            'bsb_number' => $validated['bsb_number'],
            'account_type' => $validated['account_type'],
            'currency' => $validated['currency'],
            'opening_balance' => $validated['opening_balance'],
            'opening_balance_date' => $validated['opening_balance_date'],
            'current_balance' => $validated['opening_balance'],
            'is_active' => true,
        ]);

        return redirect()->route('bank-accounts.show', [$organisation->id, $bankAccount->id])
            ->with('success', 'Bank account created successfully!');
    }

    /**
     * Display the specified bank account
     */
    public function show(Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        $transactions = $bankAccount->transactions()
            ->latest('transaction_date')
            ->paginate(500);

        // Calculate current balance: opening_balance + sum of all transactions
        $calculatedBalance = $bankAccount->opening_balance;
        $allTransactions = $bankAccount->transactions()->get();
        foreach ($allTransactions as $transaction) {
            if ($transaction->type === 'credit') {
                $calculatedBalance += $transaction->amount;
            } else {
                $calculatedBalance -= $transaction->amount;
            }
        }

        // Get the latest CSV import balance
        $latestImportBalance = $bankAccount->transactions()
            ->whereNotNull('balance')
            ->latest('transaction_date')
            ->first()?->balance;

        // Check if balances match
        $balancesMatch = false;
        if ($latestImportBalance !== null) {
            $balancesMatch = abs($calculatedBalance - $latestImportBalance) < 0.01;
        }

        return view('bank-accounts.show', [
            'organisation' => $organisation,
            'bankAccount' => $bankAccount,
            'transactions' => $transactions,
            'calculatedBalance' => $calculatedBalance,
            'latestImportBalance' => $latestImportBalance,
            'balancesMatch' => $balancesMatch,
        ]);
    }

    /**
     * Show the form for editing the specified bank account
     */
    public function edit(Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        return view('bank-accounts.edit', [
            'organisation' => $organisation,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Update the specified bank account
     */
    public function update(Request $request, Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number,' . $bankAccount->id,
            'bank_name' => 'required|string|max:255',
            'bsb_number' => 'nullable|string|max:6',
            'account_type' => 'required|in:checking,savings,credit,investment,other',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'required|numeric|min:0',
            'opening_balance_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.show', [$organisation->id, $bankAccount->id])
            ->with('success', 'Bank account updated successfully!');
    }

    /**
     * Remove the specified bank account
     */
    public function destroy(Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index', $organisation->id)
            ->with('success', 'Bank account deleted successfully!');
    }

    /**
     * Authorize organisation access
     */
    private function authorizeOrganisation(Organisation $organisation)
    {
        $user = Auth::user();

        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            abort(403, 'Unauthorized access to this organisation');
        }
    }

    /**
     * Authorize bank account access
     */
    private function authorizeBankAccount(BankAccount $bankAccount, Organisation $organisation)
    {
        if ($bankAccount->organisation_id !== $organisation->id) {
            abort(403, 'This bank account does not belong to this organisation');
        }
    }
}
