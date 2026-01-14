<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedImportTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'import_history_id',
        'row_number',
        'transaction_date',
        'description',
        'amount',
        'error_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'transaction_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the import history this failed transaction belongs to
     */
    public function importHistory()
    {
        return $this->belongsTo(ImportHistory::class);
    }
}
