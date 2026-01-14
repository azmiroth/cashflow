<?php
/**
 * CashFlowPrediction Model
 * Version: 1.0
 * Created: 2026-01-13 07:40 GMT+11
 * 
 * Represents a cash flow prediction for an organisation
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowPrediction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organisation_id',
        'created_by',
        'name',
        'description',
        'prediction_method',
        'analysis_period_days',
        'forecast_period_days',
        'analysis_start_date',
        'analysis_end_date',
        'forecast_start_date',
        'forecast_end_date',
        'confidence_level',
        'predicted_daily_average',
        'predicted_monthly_flow',
        'trend_direction',
        'trend_percentage',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'analysis_start_date' => 'date',
        'analysis_end_date' => 'date',
        'forecast_start_date' => 'date',
        'forecast_end_date' => 'date',
        'predicted_daily_average' => 'decimal:2',
        'predicted_monthly_flow' => 'decimal:2',
        'trend_percentage' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organisation this prediction belongs to
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the user who created this prediction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get bank accounts included in this prediction
     */
    public function bankAccounts()
    {
        return $this->belongsToMany(
            BankAccount::class,
            'prediction_account_selections',
            'cash_flow_prediction_id',
            'bank_account_id'
        );
    }

    /**
     * Get prediction account selections
     */
    public function accountSelections()
    {
        return $this->hasMany(PredictionAccountSelection::class);
    }

    /**
     * Get prediction data
     */
    public function getPredictionData()
    {
        return [
            'method' => $this->prediction_method,
            'daily_average' => $this->predicted_daily_average,
            'monthly_flow' => $this->predicted_monthly_flow,
            'trend' => $this->trend_direction,
            'trend_percentage' => $this->trend_percentage,
            'confidence' => $this->confidence_level,
            'forecast_days' => $this->forecast_period_days,
        ];
    }

    /**
     * Get confidence level as percentage
     */
    public function getConfidencePercentage()
    {
        return round($this->confidence_level * 100, 2);
    }

    /**
     * Check if prediction is trending up
     */
    public function isTrendingUp()
    {
        return $this->trend_direction === 'up';
    }

    /**
     * Check if prediction is trending down
     */
    public function isTrendingDown()
    {
        return $this->trend_direction === 'down';
    }

    /**
     * Check if prediction is stable
     */
    public function isStable()
    {
        return $this->trend_direction === 'stable';
    }

    /**
     * Get prediction status label
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'archived' => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Get forecast end date formatted
     */
    public function getForecastEndDateFormatted()
    {
        return $this->forecast_end_date->format('Y-m-d');
    }

    /**
     * Get analysis period label
     */
    public function getAnalysisPeriodLabel()
    {
        return $this->analysis_period_days . ' days';
    }

    /**
     * Get forecast period label
     */
    public function getForecastPeriodLabel()
    {
        return $this->forecast_period_days . ' days';
    }

    /**
     * Scope: Completed predictions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Pending predictions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: By method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('prediction_method', $method);
    }

    /**
     * Scope: High confidence (>= 0.8)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_level', '>=', 0.8);
    }

    /**
     * Scope: Recent predictions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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
