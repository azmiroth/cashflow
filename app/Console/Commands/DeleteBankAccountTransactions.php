<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Console\Command;

class DeleteBankAccountTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:delete-account {account_id : The ID of the bank account} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all transactions for a specific bank account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('account_id');
        $force = $this->option('force');

        // Find the bank account
        $bankAccount = BankAccount::find($accountId);

        if (!$bankAccount) {
            $this->error("Bank account with ID {$accountId} not found.");
            return 1;
        }

        $count = Transaction::where('bank_account_id', $accountId)->count();

        if ($count === 0) {
            $this->info('No transactions found for this bank account.');
            return 0;
        }

        // Confirm deletion
        if (!$force && !$this->confirm("Delete {$count} transactions from {$bankAccount->account_name}? This cannot be undone.")) {
            $this->info('Cancelled.');
            return 0;
        }

        // Delete transactions
        Transaction::where('bank_account_id', $accountId)->delete();

        $this->info("✓ Deleted {$count} transactions from {$bankAccount->account_name}");
        return 0;
    }
}
