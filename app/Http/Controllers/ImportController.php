<?php
/**
 * Import Controller
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ImportHistory;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);
        return view('imports.create', compact('bankAccount'));
    }

    public function store(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'date_column' => 'required|integer|min:0',
            'description_column' => 'required|integer|min:0',
            'amount_column' => 'required|integer|min:0',
            'type_column' => 'required|integer|min:0',
            'reference_column' => 'nullable|integer|min:0',
        ]);

        // Create import history record
        $importHistory = ImportHistory::create([
            'organisation_id' => $bankAccount->organisation_id,
            'bank_account_id' => $bankAccount->id,
            'filename' => $validated['file']->getClientOriginalName(),
            'imported_by' => Auth::id(),
            'status' => 'processing',
        ]);

        // Store file
        $filePath = $validated['file']->store('imports', 'local');
        $importHistory->update(['file_path' => $filePath]);

        // Process import
        $columnMapping = [
            'date' => $validated['date_column'],
            'description' => $validated['description_column'],
            'amount' => $validated['amount_column'],
            'type' => $validated['type_column'],
            'reference' => $validated['reference_column'] ?? null,
        ];

        $service = new CsvImportService($bankAccount, $importHistory);
        $result = $service->import(storage_path('app/' . $filePath), $columnMapping);

        if ($result['success']) {
            return redirect()->route('bank-accounts.index')->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    public function history(BankAccount $bankAccount)
    {
        $this->authorize('view', $bankAccount->organisation);
        $imports = $bankAccount->importHistories()->latest()->paginate(15);
        return view('imports.history', compact('bankAccount', 'imports'));
    }
}
