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

        return view('imports.index', [
            'organisation' => $organisation,
            'imports' => $imports,
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
        ]);

        $file = $request->file('csv_file');

        $importHistory = ImportHistory::create([
            'bank_account_id' => $bankAccount->id,
            'user_id' => Auth::id(),
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
                $validated['reference_column'] ?? null
            );

            $importHistory->update([
                'total_records' => $result['total'],
                'successful_records' => $result['successful'],
                'failed_records' => $result['failed'],
                'status' => $result['failed'] > 0 ? 'completed_with_errors' : 'completed',
            ]);

            return redirect()->route('imports.index', $organisation->id)
                ->with('success', "Import completed! {$result['successful']} transactions imported.");

        } catch (\Exception $e) {
            $importHistory->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->back()
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
     */
    private function processCSV($filepath, BankAccount $bankAccount, $dateCol, $descCol, $amountCol, $refCol = null)
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
                        continue;
                    }

                    $amount = $parsedAmount['amount'];
                    $type = $parsedAmount['type'];

                    // Validate required fields
                    if (!$date || !$amount) {
                        $failed++;
                        continue;
                    }

                    // Check for duplicates
                    $exists = Transaction::where('bank_account_id', $bankAccount->id)
                        ->where('transaction_date', $date)
                        ->where('amount', $amount)
                        ->where('description', $description)
                        ->exists();

                    if ($exists) {
                        $failed++;
                        continue;
                    }

                    // Create transaction
                    Transaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'transaction_date' => $date,
                        'description' => $description,
                        'amount' => $amount,
                        'type' => $type,
                        'reference' => $reference,
                        'is_reconciled' => false,
                    ]);

                    $successful++;

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row $total: " . $e->getMessage();
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
     */
    private function parseDate($dateStr)
    {
        $formats = [
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'M d, Y',
            'd M Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($dateStr));
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
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
     * Authorize organisation access
     */
    private function authorizeOrganisation(Organisation $organisation)
    {
        if ($organisation->user_id !== Auth::id()) {
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
