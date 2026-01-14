<?php
/**
 * Organisation Model
 * Version: 1.0
 * Created: 2026-01-13 07:30 GMT+11
 * 
 * Represents an organisation with bank accounts, transactions, and predictions
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'currency',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the owner of this organisation
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get members of this organisation
     */
    public function members()
    {
        return $this->belongsToMany(
            User::class,
            'organisation_members',
            'organisation_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get all users (owner + members)
     */
    public function allUsers()
    {
        return $this->owner()->union(
            $this->members()
        );
    }

    /**
     * Get bank accounts for this organisation
     */
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Get active bank accounts
     */
    public function activeBankAccounts()
    {
        return $this->bankAccounts()->where('is_active', true);
    }

    /**
     * Get transactions for this organisation
     */
    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, BankAccount::class);
    }

    /**
     * Get transaction categories
     */
    public function transactionCategories()
    {
        return $this->hasMany(TransactionCategory::class);
    }

    /**
     * Get cash flow predictions
     */
    public function predictions()
    {
        return $this->hasMany(CashFlowPrediction::class);
    }

    /**
     * Get import histories
     */
    public function importHistories()
    {
        return $this->hasMany(ImportHistory::class);
    }

    /**
     * Get total balance across all accounts
     */
    public function getTotalBalance()
    {
        return $this->activeBankAccounts()
                    ->sum('current_balance');
    }

    /**
     * Get total opening balance
     */
    public function getTotalOpeningBalance()
    {
        return $this->activeBankAccounts()
                    ->sum('opening_balance');
    }

    /**
     * Get monthly inflow
     */
    public function getMonthlyInflow()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('type', 'credit')
                    ->sum('amount');
    }

    /**
     * Get monthly outflow
     */
    public function getMonthlyOutflow()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('type', 'debit')
                    ->sum('amount');
    }

    /**
     * Get net monthly cash flow
     */
    public function getMonthlyNetFlow()
    {
        return $this->getMonthlyInflow() - $this->getMonthlyOutflow();
    }

    /**
     * Get daily cash flow for date range
     */
    public function getDailyCashFlow($startDate, $endDate)
    {
        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->selectRaw('DATE(transaction_date) as date, SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as net_flow')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
    }

    /**
     * Get account count
     */
    public function getAccountCount()
    {
        return $this->bankAccounts()->count();
    }

    /**
     * Get transaction count
     */
    public function getTransactionCount()
    {
        return $this->transactions()->count();
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions($limit = 10)
    {
        return $this->transactions()
                    ->latest('transaction_date')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get recent predictions
     */
    public function getRecentPredictions($limit = 5)
    {
        return $this->predictions()
                    ->latest('created_at')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Scope: Active organisations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Scope: By owner
     */
    public function scopeByOwner($query, $userId)
    {
        return $query->where('owner_id', $userId);
    }

    /**
     * Scope: By currency
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }
}
