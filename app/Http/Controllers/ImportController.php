<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ImportHistory;
use App\Models\Organisation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display import history
     */
    public function index(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $imports = $organisation->importHistories()
            ->latest()
            ->paginate(20);

        $bankAccounts = $organisation->bankAccounts()->get();

        return view('imports.index', [
            'organisation' => $organisation,
            'imports' => $imports,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Show the form for importing a CSV file
     */
    public function create(Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        return view('imports.create', [
            'organisation' => $organisation,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Store import and process CSV
     */
    public function store(Request $request, Organisation $organisation, BankAccount $bankAccount)
    {
        $this->authorizeOrganisation($organisation);
        $this->authorizeBankAccount($bankAccount, $organisation);

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'date_column' => 'required|integer|min:0',
            'description_column' => 'required|integer|min:0',
            'amount_column' => 'required|integer|min:0',
            'reference_column' => 'nullable|integer|min:0',
            'balance_column' => 'required|integer|min:0',
        ]);

        $file = $request->file('csv_file');

        $importHistory = ImportHistory::create([
            'organisation_id' => $organisation->id,
            'bank_account_id' => $bankAccount->id,
            'imported_by' => Auth::id(),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $file->store('imports'),
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'status' => 'processing',
        ]);

        try {
            $result = $this->processCSV(
                $file->getRealPath(),
                $bankAccount,
                $validated['date_column'],
                $validated['description_column'],
                $validated['amount_column'],
                $validated['reference_column'] ?? null,
                $validated['balance_column'] ?? null,
                $importHistory->id
            );

            $importHistory->update([
                'total_records' => $result['total'],
                'successful_records' => $result['successful'],
                'failed_records' => $result['failed'],
                'status' => $result['failed'] > 0 ? 'completed_with_errors' : 'completed',
            ]);

            return redirect("/organisations/{$organisation->id}/imports/{$importHistory->id}")
                ->with('success', "Import completed! {$result['successful']} transactions imported.");

        } catch (\Exception $e) {
            $importHistory->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return redirect("/organisations/{$organisation->id}/imports")
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show import details
     */
    public function show(Organisation $organisation, ImportHistory $import)
    {
        $this->authorizeOrganisation($organisation);

        if ($import->bankAccount->organisation_id !== $organisation->id) {
            abort(403, 'Unauthorized');
        }

        return view('imports.show', [
            'organisation' => $organisation,
            'import' => $import,
        ]);
    }

    /**
     * Process CSV file and import transactions
     * Amounts: positive = credit (deposit), negative = debit (withdrawal)
     * Balance column (if provided) is used for reconciliation
     */
    private function processCSV($filepath, BankAccount $bankAccount, $dateCol, $descCol, $amountCol, $refCol = null, $balanceCol = null, $importHistoryId = null)
    {
        $file = fopen($filepath, 'r');
        $total = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        // Skip header row
        fgetcsv($file);

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== false) {
                $total++;

                try {
                    // Extract data from columns
                    $date = $this->parseDate($row[$dateCol] ?? '');
                    $description = $row[$descCol] ?? '';
                    $rawAmount = $row[$amountCol] ?? '';
                    $reference = $refCol !== null ? ($row[$refCol] ?? null) : null;

                    // Parse amount and determine type based on sign
                    $parsedAmount = $this->parseAmountWithType($rawAmount);
                    if (!$parsedAmount) {
                        $failed++;
                        if ($importHistoryId) {
                            \App\Models\FailedImportTransaction::create([
                                'import_history_id' => $importHistoryId,
                                'row_number' => $total,
                                'transaction_date' => null,
                                'description' => $description,
                                'amount' => $rawAmount,
                                'error_reason' => 'Invalid amount format',
                            ]);
                        }
                        continue;
                    }

                    $amount = $parsedAmount['amount'];
                    $type = $parsedAmount['type'];

                    // Validate required fields
                    if (!$date || !$amount) {
                        $failed++;
                        if ($importHistoryId) {
                            \App\Models\FailedImportTransaction::create([
                                'import_history_id' => $importHistoryId,
                                'row_number' => $total,
                                'transaction_date' => $date,
                                'description' => $description,
                                'amount' => $rawAmount,
                                'error_reason' => 'Missing required fields (date or amount)',
                            ]);
                        }
                        continue;
                    }

                    // Check for duplicates only if balance column was provided
                    // A transaction is a duplicate only if it has the same running balance
                    // This allows reimporting the same CSV and only importing new transactions
                    // However, if a transaction previously failed but now has a different balance, allow it
                    $exists = false;
                    
                    if ($balanceCol !== null) {
                        // Get the balance value from the CSV
                        $csvBalance = $this->parseAmount($row[$balanceCol] ?? '');
                        
                        // Check if a transaction with this exact balance already exists
                        // If it does, it's a duplicate. If not, it's a new transaction.
                        $exists = Transaction::where('bank_account_id', $bankAccount->id)
                            ->where('balance', $csvBalance)
                            ->exists();
                        
                        // If not found in transactions, check if it was previously flagged as failed
                        // If it was failed before but now has a different balance, allow the import
                        if (!$exists) {
                            $wasPreviouslyFailed = \App\Models\FailedImportTransaction::where('transaction_date', $date)
                                ->where('description', $description)
                                ->where('amount', $rawAmount)
                                ->exists();
                            
                            // If it was previously failed, we allow it to import (different balance means it's a new attempt)
                            // If it wasn't previously failed, it's a new transaction, so allow it
                        }
                    }
                    // Note: Without balance column, we don't check for duplicates
                    // This allows multiple transactions with the same date/amount/description

                    if ($exists) {
                        $failed++;
                        if ($importHistoryId) {
                            \App\Models\FailedImportTransaction::create([
                                'import_history_id' => $importHistoryId,
                                'row_number' => $total,
                                'transaction_date' => $date,
                                'description' => $description,
                                'amount' => $rawAmount,
                                'error_reason' => 'Duplicate transaction (same balance already exists)',
                            ]);
                        }
                        continue;
                    }

                    // Calculate running balance for reconciliation
                    $isReconciled = false;
                    if ($balanceCol !== null) {
                        $csvBalance = $this->parseAmount($row[$balanceCol] ?? '');
                        if ($csvBalance !== null) {
                            // Get the running balance up to this transaction
                            $runningBalance = $this->calculateRunningBalance($bankAccount, $date, $amount, $type);
                            $isReconciled = abs($runningBalance - $csvBalance) < 0.01;
                        }
                    }

                    // Create transaction
                    $csvBalance = null;
                    if ($balanceCol !== null) {
                        $csvBalance = $this->parseAmount($row[$balanceCol] ?? '');
                    }
                    
                    Transaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'transaction_date' => $date,
                        'description' => $description,
                        'amount' => $amount,
                        'type' => $type,
                        'reference' => $reference,
                        'is_reconciled' => $isReconciled,
                        'balance' => $csvBalance,
                    ]);

                    $successful++;

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row $total: " . $e->getMessage();
                    if ($importHistoryId) {
                        \App\Models\FailedImportTransaction::create([
                            'import_history_id' => $importHistoryId,
                            'row_number' => $total,
                            'transaction_date' => $date ?? null,
                            'description' => $description ?? null,
                            'amount' => $rawAmount ?? null,
                            'error_reason' => $e->getMessage(),
                        ]);
                    }
                }
            }

            fclose($file);
            DB::commit();

            return [
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            throw $e;
        }
    }

    /**
     * Parse date from various formats
     * Supports: d/m/yyyy, dd/mm/yyyy, yyyy-mm-dd, mm/dd/yyyy, dd-mm-yyyy, yyyy/mm/dd, M d, Y, d M Y
     */
    private function parseDate($dateStr)
    {
        $dateStr = trim($dateStr);
        
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'm/d/Y',
            'Y/m/d',
            'M d, Y',
            'd M Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Parse amount from various formats (without type determination)
     */
    private function parseAmount($amountStr)
    {
        if (empty($amountStr)) {
            return null;
        }

        // Remove currency symbols and spaces
        $amount = str_replace(['$', ' '], '', trim($amountStr));
        
        // Handle European format (1.000,00)
        if (preg_match('/^-?\\d+\\.\\d{3},\\d{2}$/', $amount)) {
            $amount = str_replace(['.', ','], ['', '.'], $amount);
        } else {
            // Standard format - just remove commas
            $amount = str_replace(',', '', $amount);
        }
        
        return (float) $amount;
    }

    /**
     * Parse amount from various formats and determine type based on sign
     * Positive amount = credit (deposit)
     * Negative amount = debit (withdrawal)
     */
    private function parseAmountWithType($amountStr)
    {
        // Remove currency symbols and spaces
        $amount = str_replace(['$', ' '], '', trim($amountStr));
        
        // Handle European format (1.000,00)
        if (preg_match('/^-?\\d+\\.\\d{3},\\d{2}$/', $amount)) {
            $amount = str_replace(['.', ','], ['', '.'], $amount);
        } else {
            // Standard format - just remove commas
            $amount = str_replace(',', '', $amount);
        }
        
        $amount = (float) $amount;

        // Determine type based on sign
        if ($amount > 0) {
            return [
                'amount' => $amount,
                'type' => 'credit',
            ];
        } elseif ($amount < 0) {
            return [
                'amount' => abs($amount),
                'type' => 'debit',
            ];
        }

        return null;
    }

    /**
     * Calculate running balance up to a specific transaction
     */
    private function calculateRunningBalance(BankAccount $bankAccount, $date, $amount, $type)
    {
        // Start with opening balance
        $balance = $bankAccount->opening_balance;

        // Get all transactions up to and including this date
        $transactions = Transaction::where('bank_account_id', $bankAccount->id)
            ->where('transaction_date', '<=', $date)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->type === 'credit') {
                $balance += $transaction->amount;
            } else {
                $balance -= $transaction->amount;
            }
        }

        return $balance;
    }

    /**
     * Authorize organisation access
     */
    private function authorizeOrganisation(Organisation $organisation)
    {
        if ($organisation->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Authorize bank account access
     */
    private function authorizeBankAccount(BankAccount $bankAccount, Organisation $organisation)
    {
        if ($bankAccount->organisation_id !== $organisation->id) {
            abort(403, 'Unauthorized');
        }
    }
}
