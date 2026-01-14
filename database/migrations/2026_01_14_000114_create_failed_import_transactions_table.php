<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('failed_import_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_history_id');
            $table->integer('row_number');
            $table->date('transaction_date')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('amount', 50)->nullable();
            $table->string('error_reason', 500);
            $table->timestamps();

            $table->foreign('import_history_id')
                ->references('id')
                ->on('import_histories')
                ->onDelete('cascade');

            $table->index('import_history_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_transactions');
    }
};
