<?php
/**
 * ImportHistory Model
 * Version: 1.0
 * Created: 2026-01-13 07:45 GMT+11
 * 
 * Tracks bank statement imports
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organisation_id',
        'bank_account_id',
        'imported_by',
        'filename',
        'file_path',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'import_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organisation this import belongs to
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the bank account this import is for
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the user who imported
     */
    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate()
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->imported_records / $this->total_records) * 100, 2);
    }

    /**
     * Get skip rate percentage
     */
    public function getSkipRate()
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->skipped_records / $this->total_records) * 100, 2);
    }

    /**
     * Get error rate percentage
     */
    public function getErrorRate()
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->error_records / $this->total_records) * 100, 2);
    }

    /**
     * Check if import was successful
     */
    public function isSuccessful()
    {
        return $this->status === 'completed' && $this->error_records === 0;
    }

    /**
     * Check if import has errors
     */
    public function hasErrors()
    {
        return $this->error_records > 0;
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get import summary
     */
    public function getSummary()
    {
        return [
            'total' => $this->total_records,
            'imported' => $this->imported_records,
            'skipped' => $this->skipped_records,
            'errors' => $this->error_records,
            'success_rate' => $this->getSuccessRate(),
            'skip_rate' => $this->getSkipRate(),
            'error_rate' => $this->getErrorRate(),
        ];
    }

    /**
     * Scope: Completed imports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed imports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: By account
     */
    public function scopeByAccount($query, $accountId)
    {
        return $query->where('bank_account_id', $accountId);
    }

    /**
     * Scope: By organisation
     */
    public function scopeByOrganisation($query, $organisationId)
    {
        return $query->where('organisation_id', $organisationId);
    }

    /**
     * Scope: Recent imports
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: With errors
     */
    public function scopeWithErrors($query)
    {
        return $query->where('error_records', '>', 0);
    }
}
