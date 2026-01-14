<?php
/**
 * TransactionCategory Model
 * Version: 1.0
 * Created: 2026-01-13 07:35 GMT+11
 * 
 * Represents a transaction category for classification
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organisation_id',
        'name',
        'description',
        'color',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organisation this category belongs to
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get transactions in this category
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * Get category total amount
     */
    public function getTotalAmount()
    {
        return $this->transactions()->sum('amount');
    }

    /**
     * Get category transaction count
     */
    public function getTransactionCount()
    {
        return $this->transactions()->count();
    }

    /**
     * Get category average transaction amount
     */
    public function getAverageAmount()
    {
        $count = $this->getTransactionCount();
        if ($count === 0) {
            return 0;
        }

        return $this->getTotalAmount() / $count;
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
     * Scope: Active categories only
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
}
