<?php
/**
 * CSV Import Service
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\ImportHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CsvImportService
{
    protected $bankAccount;
    protected $importHistory;
    protected $successCount = 0;
    protected $failureCount = 0;
    protected $errors = [];

    public function __construct(BankAccount $bankAccount, ImportHistory $importHistory = null)
    {
        $this->bankAccount = $bankAccount;
        $this->importHistory = $importHistory;
    }

    /**
     * Import transactions from CSV file
     */
    public function import(string $filePath, array $columnMapping): array
    {
        try {
            $this->successCount = 0;
            $this->failureCount = 0;
            $this->errors = [];

            if (!file_exists($filePath)) {
                throw new \Exception('File not found');
            }

            $file = fopen($filePath, 'r');
            if (!$file) {
                throw new \Exception('Unable to open file');
            }

            $header = fgetcsv($file);
            $rowNumber = 1;

            DB::beginTransaction();

            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;

                try {
                    $this->processRow($row, $header, $columnMapping);
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failureCount++;
                    $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                }
            }

            fclose($file);

            // Update account balance
            $this->bankAccount->updateBalance();

            // Update import history if provided
            if ($this->importHistory) {
                $this->importHistory->update([
                    'total_records' => $this->successCount + $this->failureCount,
                    'successful_records' => $this->successCount,
                    'failed_records' => $this->failureCount,
                    'status' => $this->failureCount === 0 ? 'completed' : 'completed',
                    'error_message' => !empty($this->errors) ? json_encode($this->errors) : null,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Import completed: {$this->successCount} successful, {$this->failureCount} failed",
                'successful_records' => $this->successCount,
                'failed_records' => $this->failureCount,
                'errors' => $this->errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->importHistory) {
                $this->importHistory->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process individual row
     */
    protected function processRow(array $row, array $header, array $columnMapping): void
    {
        $data = [];

        foreach ($columnMapping as $field => $columnIndex) {
            if (isset($row[$columnIndex])) {
                $data[$field] = trim($row[$columnIndex]);
            }
        }

        // Validate required fields
        if (empty($data['date']) || empty($data['description']) || empty($data['amount']) || empty($data['type'])) {
            throw new \Exception('Missing required fields');
        }

        // Parse date
        $transactionDate = $this->parseDate($data['date']);
        if (!$transactionDate) {
            throw new \Exception("Invalid date format: {$data['date']}");
        }

        // Parse amount
        $amount = $this->parseAmount($data['amount']);
        if ($amount === null) {
            throw new \Exception("Invalid amount: {$data['amount']}");
        }

        // Parse transaction type
        $type = $this->parseType($data['type']);
        if (!$type) {
            throw new \Exception("Invalid transaction type: {$data['type']}");
        }

        // Check for duplicates
        $existing = Transaction::where('bank_account_id', $this->bankAccount->id)
            ->where('transaction_date', $transactionDate)
            ->where('amount', $amount)
            ->where('description', $data['description'])
            ->exists();

        if ($existing) {
            throw new \Exception('Duplicate transaction');
        }

        // Create transaction
        Transaction::create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $transactionDate,
            'description' => $data['description'],
            'amount' => $amount,
            'transaction_type' => $type,
            'reference' => $data['reference'] ?? null,
        ]);
    }

    /**
     * Parse date in various formats
     */
    protected function parseDate(string $dateString): ?Carbon
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
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Parse amount in various formats
     */
    protected function parseAmount(string $amountString): ?float
    {
        // Remove currency symbols
        $amount = preg_replace('/[^\d.,\-]/', '', $amountString);

        // Handle different decimal separators
        if (strpos($amount, ',') !== false && strpos($amount, '.') !== false) {
            // Both exist, determine which is decimal
            $lastComma = strrpos($amount, ',');
            $lastDot = strrpos($amount, '.');
            if ($lastDot > $lastComma) {
                $amount = str_replace(',', '', $amount);
            } else {
                $amount = str_replace('.', '', $amount);
                $amount = str_replace(',', '.', $amount);
            }
        } elseif (strpos($amount, ',') !== false) {
            $amount = str_replace(',', '.', $amount);
        }

        return (float) $amount ?: null;
    }

    /**
     * Parse transaction type
     */
    protected function parseType(string $typeString): ?string
    {
        $type = strtolower(trim($typeString));

        if (in_array($type, ['credit', 'in', '+', 'deposit', 'income'])) {
            return 'credit';
        } elseif (in_array($type, ['debit', 'out', '-', 'withdrawal', 'expense'])) {
            return 'debit';
        }

        return null;
    }
}
