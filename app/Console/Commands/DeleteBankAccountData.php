<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\ImportHistory;
use App\Models\Transaction;
use Illuminate\Console\Command;

class DeleteBankAccountData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bankaccount:delete-all-data {account_id} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all transactions and import history for a bank account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('account_id');
        $force = $this->option('force');

        $bankAccount = BankAccount::find($accountId);
        if (!$bankAccount) {
            $this->error("Bank account with ID {$accountId} not found.");
            return 1;
        }

        $this->info("Bank Account: {$bankAccount->account_name} ({$bankAccount->bank_name})");
        
        $transactionCount = Transaction::where('bank_account_id', $accountId)->count();
        $importCount = ImportHistory::where('bank_account_id', $accountId)->count();

        $this->warn("This will delete:");
        $this->warn("  - {$transactionCount} transactions");
        $this->warn("  - {$importCount} import histories");
        $this->warn("  - All failed import records");

        if (!$force && !$this->confirm('Are you sure you want to delete all this data?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        try {
            // Delete failed import transactions
            \DB::table('failed_import_transactions')
                ->whereIn('import_history_id', 
                    ImportHistory::where('bank_account_id', $accountId)->pluck('id')
                )
                ->delete();

            // Delete import histories
            ImportHistory::where('bank_account_id', $accountId)->delete();

            // Delete transactions
            Transaction::where('bank_account_id', $accountId)->delete();

            $this->info('✓ All data deleted successfully!');
            $this->info("  - Deleted {$transactionCount} transactions");
            $this->info("  - Deleted {$importCount} import histories");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error deleting data: ' . $e->getMessage());
            return 1;
        }
    }
}
