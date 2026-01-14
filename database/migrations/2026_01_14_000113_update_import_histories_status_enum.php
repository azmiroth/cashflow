<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to modify the enum column
        DB::statement("ALTER TABLE import_histories MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'completed_with_errors', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE import_histories MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'");
    }
};
