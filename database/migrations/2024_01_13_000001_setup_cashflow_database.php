<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This is a comprehensive setup migration that:
     * 1. Disables foreign key checks
     * 2. Drops all existing CashFlow tables
     * 3. Creates all tables from scratch
     * 4. Re-enables foreign key checks
     */
    public function up(): void
    {
        // Disable foreign key checks for cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Drop all CashFlow tables if they exist
            Schema::dropIfExists('import_histories');
            Schema::dropIfExists('prediction_account_selections');
            Schema::dropIfExists('cash_flow_predictions');
            Schema::dropIfExists('transactions');
            Schema::dropIfExists('transaction_categories');
            Schema::dropIfExists('bank_accounts');
            Schema::dropIfExists('organisations');

            // Create organisations table
            Schema::create('organisations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id');
                $table->string('name', 255);
                $table->longText('description')->nullable();
                $table->string('currency', 3)->default('USD');
                $table->string('fiscal_year_start', 5)->default('01-01');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('owner_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->index('owner_id');
                $table->index('created_at');
            });

            // Create bank_accounts table
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->string('account_name', 255);
                $table->string('account_number', 255)->unique();
                $table->string('bank_name', 255);
                $table->string('bsb_number', 6)->nullable();
                $table->string('account_type', 50)->default('checking');
                $table->string('currency', 3)->default('USD');
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->date('opening_balance_date')->nullable();
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->index('organisation_id');
                $table->index('created_at');
            });

            // Create transaction_categories table
            Schema::create('transaction_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->string('name', 255);
                $table->longText('description')->nullable();
                $table->string('color', 7)->default('#667eea');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->index('organisation_id');
            });

            // Create transactions table
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_account_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->date('transaction_date');
                $table->string('description', 500);
                $table->decimal('amount', 15, 2);
                $table->enum('type', ['credit', 'debit']);
                $table->string('reference', 255)->nullable();
                $table->boolean('is_reconciled')->default(false);
                $table->timestamps();

                $table->foreign('bank_account_id')
                    ->references('id')
                    ->on('bank_accounts')
                    ->onDelete('cascade');

                $table->foreign('category_id')
                    ->references('id')
                    ->on('transaction_categories')
                    ->onDelete('set null');

                $table->index('bank_account_id');
                $table->index('transaction_date');
                $table->index('category_id');
                $table->index(['bank_account_id', 'transaction_date']);
            });

            // Create cash_flow_predictions table
            Schema::create('cash_flow_predictions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->string('prediction_name', 255);
                $table->integer('analysis_period_days')->default(30);
                $table->integer('forecast_period_days')->default(30);
                $table->string('prediction_method', 50)->default('moving_average');
                $table->decimal('predicted_balance', 15, 2)->default(0);
                $table->decimal('confidence_level', 5, 2)->default(0);
                $table->enum('trend', ['increasing', 'decreasing', 'stable'])->default('stable');
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->index('organisation_id');
                $table->index('created_by');
                $table->index('created_at');
            });

            // Create prediction_account_selections table
            Schema::create('prediction_account_selections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cash_flow_prediction_id');
                $table->unsignedBigInteger('bank_account_id');
                $table->timestamps();

                $table->foreign('cash_flow_prediction_id')
                    ->references('id')
                    ->on('cash_flow_predictions')
                    ->onDelete('cascade');

                $table->foreign('bank_account_id')
                    ->references('id')
                    ->on('bank_accounts')
                    ->onDelete('cascade');

                // Use short name to avoid MySQL identifier length limit (64 chars)
                $table->unique(['cash_flow_prediction_id', 'bank_account_id'], 'pred_acct_unique');
            });

            // Create import_histories table
            Schema::create('import_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('bank_account_id');
                $table->string('filename', 500);
                $table->string('file_path', 500)->nullable();
                $table->unsignedBigInteger('imported_by');
                $table->integer('total_records')->default(0);
                $table->integer('successful_records')->default(0);
                $table->integer('failed_records')->default(0);
                $table->enum('status', ['pending', 'processing', 'completed', 'completed_with_errors', 'failed'])->default('pending');
                $table->longText('error_message')->nullable();
                $table->timestamps();

                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->foreign('bank_account_id')
                    ->references('id')
                    ->on('bank_accounts')
                    ->onDelete('cascade');

                $table->foreign('imported_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->index('organisation_id');
                $table->index('bank_account_id');
                $table->index('created_at');
                $table->index('status');
            });

        } finally {
            // Always re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     * This will drop all CashFlow tables.
     */
    public function down(): void
    {
        // Disable foreign key checks for cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            Schema::dropIfExists('import_histories');
            Schema::dropIfExists('prediction_account_selections');
            Schema::dropIfExists('cash_flow_predictions');
            Schema::dropIfExists('transactions');
            Schema::dropIfExists('transaction_categories');
            Schema::dropIfExists('bank_accounts');
            Schema::dropIfExists('organisations');
        } finally {
            // Always re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
