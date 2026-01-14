<?php
/**
 * PredictionAccountSelection Model
 * Version: 1.0
 * Created: 2026-01-13 07:40 GMT+11
 * 
 * Junction table linking predictions to bank accounts
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredictionAccountSelection extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'prediction_account_selections';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cash_flow_prediction_id',
        'bank_account_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Disable timestamps if not needed
     */
    public $timestamps = true;

    /**
     * Get the prediction
     */
    public function prediction()
    {
        return $this->belongsTo(CashFlowPrediction::class, 'cash_flow_prediction_id');
    }

    /**
     * Get the bank account
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
