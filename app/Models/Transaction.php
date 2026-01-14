<?php
/**
 * Transaction Model
 * Version: 1.0
 * Created: 2026-01-13 07:35 GMT+11
 * 
 * Represents a bank transaction
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bank_account_id',
        'category_id',
        'transaction_date',
        'description',
        'amount',
        'type',
        'reference',
        'is_reconciled',
        'balance',
        'excluded_from_analysis',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'excluded_from_analysis' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the bank account this transaction belongs to
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the category of this transaction
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    /**
     * Get the organisation through bank account
     */
    public function organisation()
    {
        return $this->hasOneThrough(
            Organisation::class,
            BankAccount::class,
            'id',
            'id',
            'bank_account_id',
            'organisation_id'
        );
    }

    /**
     * Get transaction amount (positive for all)
     */
    public function getAmountAttribute()
    {
        return abs($this->attributes['amount']);
    }

    /**
     * Get signed amount (negative for debit, positive for credit)
     */
    public function getSignedAmount()
    {
        $amount = $this->attributes['amount'];
        return $this->type === 'debit' ? -$amount : $amount;
    }

    /**
     * Check if transaction is a debit
     */
    public function isDebit()
    {
        return $this->type === 'debit';
    }

    /**
     * Check if transaction is a credit
     */
    public function isCredit()
    {
        return $this->type === 'credit';
    }

    /**
     * Mark as reconciled
     */
    public function markReconciled()
    {
        $this->is_reconciled = true;
        $this->save();
    }

    /**
     * Mark as unreconciled
     */
    public function markUnreconciled()
    {
        $this->is_reconciled = false;
        $this->save();
    }

    /**
     * Scope: Debits only
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope: Credits only
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope: Reconciled
     */
    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    /**
     * Scope: Unreconciled
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    /**
     * Scope: By date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Search by description or reference
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('description', 'like', "%{$term}%")
                     ->orWhere('reference', 'like', "%{$term}%");
    }

    /**
     * Scope: Amount greater than
     */
    public function scopeAmountGreaterThan($query, $amount)
    {
        return $query->where('amount', '>', $amount);
    }

    /**
     * Scope: Amount less than
     */
    public function scopeAmountLessThan($query, $amount)
    {
        return $query->where('amount', '<', $amount);
    }
}
