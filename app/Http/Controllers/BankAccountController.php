<?php
/**
 * Bank Account Controller
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

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

    public function index()
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        $bankAccounts = $organisation->bankAccounts()->get();
        return view('bank-accounts.index', compact('organisation', 'bankAccounts'));
    }

    public function create()
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        return view('bank-accounts.create', compact('organisation'));
    }

    public function store(Request $request)
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:bank_accounts',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'required|in:checking,savings,credit,other',
            'currency' => 'required|string|max:3',
            'opening_balance' => 'required|numeric',
        ]);

        $organisation->bankAccounts()->create($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account created successfully');
    }

    public function edit(BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'required|in:checking,savings,credit,other',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account updated successfully');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);
        $bankAccount->delete();
        return redirect()->route('bank-accounts.index')->with('success', 'Bank account deleted successfully');
    }
}
