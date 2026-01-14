<?php
/**
 * Prediction Engine Service
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

namespace App\Services;

use App\Models\CashFlowPrediction;
use App\Models\BankAccount;
use Carbon\Carbon;

class PredictionEngineService
{
    /**
     * Create prediction using moving average method
     */
    public function predictMovingAverage(
        array $bankAccountIds,
        int $analysisPeriodDays,
        int $forecastPeriodDays
    ): array {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($analysisPeriodDays);

        // Get all transactions for the period
        $transactions = $this->getTransactionsForAccounts($bankAccountIds, $startDate, $endDate);

        if ($transactions->isEmpty()) {
            return [
                'predicted_balance' => 0,
                'confidence_level' => 0,
                'trend' => 'stable',
            ];
        }

        // Calculate daily cash flow
        $dailyCashFlow = $this->calculateDailyCashFlow($transactions);

        if (empty($dailyCashFlow)) {
            return [
                'predicted_balance' => 0,
                'confidence_level' => 0,
                'trend' => 'stable',
            ];
        }

        // Calculate statistics
        $avgDailyFlow = array_sum($dailyCashFlow) / count($dailyCashFlow);
        $stdDev = $this->calculateStandardDeviation($dailyCashFlow);

        // Calculate 7-day moving average for trend
        $recentFlow = array_slice($dailyCashFlow, -7);
        $recentAvg = array_sum($recentFlow) / count($recentFlow);

        // Determine trend
        $trend = $this->determineTrend($avgDailyFlow, $recentAvg);

        // Project forward
        $projectedFlow = $avgDailyFlow * $forecastPeriodDays;

        // Calculate confidence level
        $confidence = $this->calculateConfidence($stdDev, $avgDailyFlow);

        // Get current balance
        $currentBalance = $this->getCurrentBalance($bankAccountIds);

        return [
            'predicted_balance' => $currentBalance + $projectedFlow,
            'confidence_level' => $confidence,
            'trend' => $trend,
        ];
    }

    /**
     * Create prediction using trend analysis method
     */
    public function predictTrendAnalysis(
        array $bankAccountIds,
        int $analysisPeriodDays,
        int $forecastPeriodDays
    ): array {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($analysisPeriodDays);

        // Get all transactions for the period
        $transactions = $this->getTransactionsForAccounts($bankAccountIds, $startDate, $endDate);

        if ($transactions->isEmpty()) {
            return [
                'predicted_balance' => 0,
                'confidence_level' => 0,
                'trend' => 'stable',
            ];
        }

        // Calculate daily cash flow
        $dailyCashFlow = $this->calculateDailyCashFlow($transactions);

        if (empty($dailyCashFlow)) {
            return [
                'predicted_balance' => 0,
                'confidence_level' => 0,
                'trend' => 'stable',
            ];
        }

        // Perform linear regression
        $regression = $this->linearRegression(array_values($dailyCashFlow));

        // Determine trend
        $trend = $regression['slope'] > 0 ? 'increasing' : ($regression['slope'] < 0 ? 'decreasing' : 'stable');

        // Project forward
        $projectedFlow = 0;
        for ($i = 1; $i <= $forecastPeriodDays; $i++) {
            $projectedFlow += $regression['slope'] * $i + $regression['intercept'];
        }

        // Calculate confidence based on R-squared
        $confidence = min(100, max(0, $regression['r_squared'] * 100));

        // Get current balance
        $currentBalance = $this->getCurrentBalance($bankAccountIds);

        return [
            'predicted_balance' => $currentBalance + $projectedFlow,
            'confidence_level' => $confidence,
            'trend' => $trend,
        ];
    }

    /**
     * Get transactions for multiple accounts
     */
    protected function getTransactionsForAccounts(array $bankAccountIds, Carbon $startDate, Carbon $endDate)
    {
        return \DB::table('transactions')
            ->whereIn('bank_account_id', $bankAccountIds)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('transaction_date', 'amount', 'transaction_type')
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Calculate daily cash flow
     */
    protected function calculateDailyCashFlow($transactions): array
    {
        $dailyFlow = [];

        foreach ($transactions as $transaction) {
            $date = $transaction->transaction_date;
            $amount = $transaction->transaction_type === 'credit' ? $transaction->amount : -$transaction->amount;

            if (!isset($dailyFlow[$date])) {
                $dailyFlow[$date] = 0;
            }

            $dailyFlow[$date] += $amount;
        }

        return array_values($dailyFlow);
    }

    /**
     * Calculate standard deviation
     */
    protected function calculateStandardDeviation(array $data): float
    {
        if (count($data) < 2) {
            return 0;
        }

        $mean = array_sum($data) / count($data);
        $squaredDifferences = array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data);

        return sqrt(array_sum($squaredDifferences) / count($squaredDifferences));
    }

    /**
     * Determine trend direction
     */
    protected function determineTrend(float $avgFlow, float $recentAvg): string
    {
        $threshold = abs($avgFlow) * 0.1; // 10% threshold

        if (abs($recentAvg - $avgFlow) < $threshold) {
            return 'stable';
        }

        return $recentAvg > $avgFlow ? 'increasing' : 'decreasing';
    }

    /**
     * Calculate confidence level
     */
    protected function calculateConfidence(float $stdDev, float $avgFlow): float
    {
        if ($avgFlow == 0) {
            return 50;
        }

        $coefficientOfVariation = $stdDev / abs($avgFlow);

        // Convert CV to confidence (lower CV = higher confidence)
        $confidence = max(0, min(100, 100 - ($coefficientOfVariation * 50)));

        return round($confidence, 2);
    }

    /**
     * Get current balance for accounts
     */
    protected function getCurrentBalance(array $bankAccountIds): float
    {
        return BankAccount::whereIn('id', $bankAccountIds)
            ->sum('current_balance');
    }

    /**
     * Linear regression calculation
     */
    protected function linearRegression(array $y): array
    {
        $n = count($y);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }

        $x = range(1, $n);

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $yPred = $slope * $x[$i] + $intercept;
            $ssTotal += pow($y[$i] - $yMean, 2);
            $ssResidual += pow($y[$i] - $yPred, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared)),
        ];
    }
}
