<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Organisation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Toggle exclusion status of a transaction
     */
    public function toggleExclusion(Organisation $organisation, BankAccount $bankAccount, Transaction $transaction, Request $request)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        // Verify transaction belongs to this bank account
        if ($transaction->bank_account_id !== $bankAccount->id) {
            abort(403);
        }

        $transaction->excluded_from_analysis = !$transaction->excluded_from_analysis;
        $transaction->save();

        $message = 'Transaction ' . ($transaction->excluded_from_analysis ? 'excluded' : 'included') . ' from analysis';

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'excluded_from_analysis' => $transaction->excluded_from_analysis,
                'message' => $message
            ]);
        }

        // Return redirect for regular requests
        return back()->with('success', $message);
    }

    /**
     * Helper methods for authorization
     */
    private function authorizeOrganisation($organisation)
    {
        $user = Auth::user();
        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            abort(403);
        }
    }

    private function authorizeBankAccount($bankAccount, $organisation)
    {
        if ($bankAccount->organisation_id !== $organisation->id) {
            abort(403);
        }
    }
}
