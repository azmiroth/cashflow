<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckMigrationReadiness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashflow:check-readiness';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the system is ready for CashFlow migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking CashFlow Migration Readiness...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // Check PHP version
        $this->line('Checking PHP version...');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $errors[] = '❌ PHP 8.1.0 or higher required. Current: ' . PHP_VERSION;
        } else {
            $this->line('✅ PHP version: ' . PHP_VERSION);
        }

        // Check Laravel version
        $this->line('Checking Laravel version...');
        $laravelVersion = app()->version();
        $this->line("✅ Laravel version: {$laravelVersion}");

        // Check database connection
        $this->line('Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->line('✅ Database connection successful');
        } catch (\Exception $e) {
            $errors[] = '❌ Database connection failed: ' . $e->getMessage();
        }

        // Check MySQL version
        $this->line('Checking MySQL version...');
        try {
            $version = DB::select('SELECT VERSION() as version')[0]->version ?? null;
            if ($version) {
                if (strpos($version, '8.0') === 0) {
                    $this->line("✅ MySQL version: {$version}");
                } else {
                    $warnings[] = "⚠️  MySQL version: {$version} (8.0+ recommended)";
                }
            }
        } catch (\Exception $e) {
            $errors[] = '❌ Could not determine MySQL version: ' . $e->getMessage();
        }

        // Check if CashFlow tables exist
        $this->line('Checking for existing CashFlow tables...');
        $tables = [
            'organisations',
            'bank_accounts',
            'transaction_categories',
            'transactions',
            'cash_flow_predictions',
            'prediction_account_selections',
            'import_histories'
        ];

        $existingTables = [];
        foreach ($tables as $table) {
            if (DB::connection()->getSchemaBuilder()->hasTable($table)) {
                $existingTables[] = $table;
            }
        }

        if (!empty($existingTables)) {
            $warnings[] = '⚠️  Found existing CashFlow tables: ' . implode(', ', $existingTables);
            $warnings[] = '    These will be dropped during migration setup';
        } else {
            $this->line('✅ No existing CashFlow tables found');
        }

        // Check migrations table
        $this->line('Checking migrations table...');
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('migrations')) {
                $this->line('✅ Migrations table exists');
            } else {
                $errors[] = '❌ Migrations table not found. Run: php artisan migrate:install';
            }
        } catch (\Exception $e) {
            $errors[] = '❌ Could not check migrations table: ' . $e->getMessage();
        }

        // Check file permissions
        $this->line('Checking file permissions...');
        $paths = [
            'storage' => base_path('storage'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'database' => base_path('database')
        ];

        foreach ($paths as $name => $path) {
            if (is_writable($path)) {
                $this->line("✅ {$name} is writable");
            } else {
                $errors[] = "❌ {$name} is not writable. Run: chmod -R 775 {$path}";
            }
        }

        // Check environment file
        $this->line('Checking .env file...');
        if (file_exists(base_path('.env'))) {
            $this->line('✅ .env file exists');
        } else {
            $errors[] = '❌ .env file not found. Copy from .env.example';
        }

        // Summary
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════');

        if (empty($errors) && empty($warnings)) {
            $this->info('✅ System is ready for CashFlow migrations!');
            $this->line('Run: php artisan migrate');
            return 0;
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line($warning);
            }
            $this->newLine();
        }

        if (!empty($errors)) {
            $this->error('❌ Errors found:');
            foreach ($errors as $error) {
                $this->line($error);
            }
            $this->newLine();
            $this->error('Please fix the errors above before running migrations.');
            return 1;
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings found, but system may still be ready.');
            $this->line('Run: php artisan migrate');
            return 0;
        }

        return 0;
    }
}
