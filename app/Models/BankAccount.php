<?php
/**
 * BankAccount Model
 * Version: 1.0
 * Created: 2026-01-13 07:30 GMT+11
 * 
 * Represents a bank account with transactions and imports
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organisation_id',
        'account_name',
        'account_number',
        'bank_name',
        'account_type',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organisation this account belongs to
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get transactions for this account
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get import histories for this account
     */
    public function importHistories()
    {
        return $this->hasMany(ImportHistory::class);
    }

    /**
     * Get predictions that include this account
     */
    public function predictions()
    {
        return $this->belongsToMany(
            CashFlowPrediction::class,
            'prediction_account_selections',
            'bank_account_id',
            'cash_flow_prediction_id'
        );
    }

    /**
     * Get account balance
     */
    public function getBalance()
    {
        return $this->current_balance;
    }

    /**
     * Get total inflow
     */
    public function getTotalInflow()
    {
        return $this->transactions()
                    ->where('type', 'credit')
                    ->sum('amount');
    }

    /**
     * Get total outflow
     */
    public function getTotalOutflow()
    {
        return $this->transactions()
                    ->where('type', 'debit')
                    ->sum('amount');
    }

    /**
     * Get net flow
     */
    public function getNetFlow()
    {
        return $this->getTotalInflow() - $this->getTotalOutflow();
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
     * Get monthly net flow
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
     * Get average daily transaction amount
     */
    public function getAverageDailyAmount()
    {
        $count = $this->transactions()->count();
        if ($count === 0) {
            return 0;
        }

        return $this->getNetFlow() / $count;
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
     * Get last import date
     */
    public function getLastImportDate()
    {
        return $this->importHistories()
                    ->where('status', 'completed')
                    ->latest('created_at')
                    ->first()?->created_at;
    }

    /**
     * Update balance
     */
    public function updateBalance($amount)
    {
        $this->current_balance += $amount;
        $this->save();
    }

    /**
     * Scope: Active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By account type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope: By currency
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope: Search by name or number
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('account_name', 'like', "%{$term}%")
                     ->orWhere('account_number', 'like', "%{$term}%")
                     ->orWhere('bank_name', 'like', "%{$term}%");
    }
}
