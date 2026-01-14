<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashFlowPrediction;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredictionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of predictions
     */
    public function index(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $predictions = $organisation->predictions()
            ->latest()
            ->paginate(20);

        return view('predictions.index', [
            'organisation' => $organisation,
            'predictions' => $predictions,
        ]);
    }

    /**
     * Show the form for creating a new prediction
     */
    public function create(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $bankAccounts = $organisation->bankAccounts()
            ->where('is_active', true)
            ->get();

        return view('predictions.create', [
            'organisation' => $organisation,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store a newly created prediction
     */
    public function store(Request $request, Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $validated = $request->validate([
            'prediction_name' => 'required|string|max:255',
            'bank_account_ids' => 'required|array|min:1',
            'bank_account_ids.*' => 'integer|exists:bank_accounts,id',
            'analysis_period_days' => 'required|in:30,60,90,180,365',
            'forecast_period_days' => 'required|in:7,14,30,60,90',
            'prediction_method' => 'required|in:moving_average,trend_analysis',
        ]);

        $user = Auth::user();

        // Verify all bank accounts belong to this organisation
        $bankAccounts = BankAccount::whereIn('id', $validated['bank_account_ids'])
            ->where('organisation_id', $organisation->id)
            ->get();

        if ($bankAccounts->count() !== count($validated['bank_account_ids'])) {
            return redirect()->back()
                ->with('error', 'One or more bank accounts do not belong to this organisation');
        }

        // Generate prediction
        $result = $this->generatePrediction(
            $bankAccounts,
            $validated['analysis_period_days'],
            $validated['forecast_period_days'],
            $validated['prediction_method']
        );

        // Create prediction record
        $prediction = CashFlowPrediction::create([
            'organisation_id' => $organisation->id,
            'prediction_name' => $validated['prediction_name'],
            'analysis_period_days' => $validated['analysis_period_days'],
            'forecast_period_days' => $validated['forecast_period_days'],
            'prediction_method' => $validated['prediction_method'],
            'predicted_balance' => $result['predicted_balance'],
            'confidence_level' => $result['confidence'],
            'trend' => $result['trend'],
            'created_by' => $user->id,
        ]);

        // Store selected bank accounts
        foreach ($validated['bank_account_ids'] as $accountId) {
            $prediction->accountSelections()->create([
                'bank_account_id' => $accountId,
            ]);
        }

        return redirect()->route('predictions.show', [$organisation->id, $prediction->id])
            ->with('success', 'Prediction created successfully!');
    }

    /**
     * Display the specified prediction
     */
    public function show(Organisation $organisation, CashFlowPrediction $prediction)
    {
        $this->authorizeOrganisation($organisation);

        if ($prediction->organisation_id !== $organisation->id) {
            abort(403);
        }

        $accountSelections = $prediction->accountSelections()
            ->with('bankAccount')
            ->get();

        return view('predictions.show', [
            'organisation' => $organisation,
            'prediction' => $prediction,
            'accountSelections' => $accountSelections,
        ]);
    }

    /**
     * Delete a prediction
     */
    public function destroy(Organisation $organisation, CashFlowPrediction $prediction)
    {
        $this->authorizeOrganisation($organisation);

        if ($prediction->organisation_id !== $organisation->id) {
            abort(403);
        }

        $prediction->delete();

        return redirect()->route('predictions.index', $organisation->id)
            ->with('success', 'Prediction deleted successfully!');
    }

    /**
     * Generate cash flow prediction
     */
    private function generatePrediction($bankAccounts, $analysisDays, $forecastDays, $method)
    {
        $startDate = now()->subDays($analysisDays);
        $endDate = now();

        // Get all transactions for the analysis period
        $transactions = [];
        foreach ($bankAccounts as $account) {
            $txns = $account->transactions()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get();
            $transactions = array_merge($transactions, $txns->toArray());
        }

        if (empty($transactions)) {
            return [
                'predicted_balance' => 0,
                'confidence' => 0,
                'trend' => 'insufficient_data',
            ];
        }

        if ($method === 'moving_average') {
            return $this->movingAveragePrediction($bankAccounts, $transactions, $analysisDays, $forecastDays);
        } else {
            return $this->trendAnalysisPrediction($bankAccounts, $transactions, $analysisDays, $forecastDays);
        }
    }

    /**
     * Moving Average prediction method
     */
    private function movingAveragePrediction($bankAccounts, $transactions, $analysisDays, $forecastDays)
    {
        $currentBalance = 0;
        foreach ($bankAccounts as $account) {
            $currentBalance += $account->current_balance;
        }

        // Calculate daily flows
        $dailyFlows = [];
        foreach ($transactions as $txn) {
            $date = $txn['transaction_date'];
            $amount = $txn['type'] === 'credit' ? $txn['amount'] : -$txn['amount'];

            if (!isset($dailyFlows[$date])) {
                $dailyFlows[$date] = 0;
            }
            $dailyFlows[$date] += $amount;
        }

        // Calculate average daily flow
        $avgDailyFlow = array_sum($dailyFlows) / count($dailyFlows);
        $stdDev = $this->calculateStdDev(array_values($dailyFlows), $avgDailyFlow);

        // Predict future balance
        $predictedBalance = $currentBalance + ($avgDailyFlow * $forecastDays);

        // Calculate confidence
        $confidence = min(100, max(0, 100 - (($stdDev / abs($avgDailyFlow)) * 50)));

        // Determine trend
        $trend = $avgDailyFlow > 0 ? 'increasing' : ($avgDailyFlow < 0 ? 'decreasing' : 'stable');

        return [
            'predicted_balance' => round($predictedBalance, 2),
            'confidence' => round($confidence, 2),
            'trend' => $trend,
        ];
    }

    /**
     * Trend Analysis prediction method
     */
    private function trendAnalysisPrediction($bankAccounts, $transactions, $analysisDays, $forecastDays)
    {
        $currentBalance = 0;
        foreach ($bankAccounts as $account) {
            $currentBalance += $account->current_balance;
        }

        // Calculate daily flows
        $dailyFlows = [];
        $dates = [];
        for ($i = $analysisDays; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $dailyFlows[] = 0;
        }

        foreach ($transactions as $txn) {
            $date = $txn['transaction_date'];
            $amount = $txn['type'] === 'credit' ? $txn['amount'] : -$txn['amount'];

            $key = array_search($date, $dates);
            if ($key !== false) {
                $dailyFlows[$key] += $amount;
            }
        }

        // Linear regression
        $n = count($dailyFlows);
        $x = range(0, $n - 1);
        $y = $dailyFlows;

        $xMean = array_sum($x) / $n;
        $yMean = array_sum($y) / $n;

        $numerator = 0;
        $denominator = 0;
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $xMean) * ($y[$i] - $yMean);
            $denominator += ($x[$i] - $xMean) ** 2;
        }

        $slope = $denominator !== 0 ? $numerator / $denominator : 0;
        $intercept = $yMean - ($slope * $xMean);

        // Predict future balance
        $predictedFlow = 0;
        for ($i = 1; $i <= $forecastDays; $i++) {
            $predictedFlow += ($slope * ($n + $i)) + $intercept;
        }

        $predictedBalance = $currentBalance + $predictedFlow;

        // Calculate R-squared for confidence
        $ssRes = 0;
        $ssTot = 0;
        for ($i = 0; $i < $n; $i++) {
            $predicted = ($slope * $x[$i]) + $intercept;
            $ssRes += ($y[$i] - $predicted) ** 2;
            $ssTot += ($y[$i] - $yMean) ** 2;
        }

        $rSquared = $ssTot !== 0 ? 1 - ($ssRes / $ssTot) : 0;
        $confidence = max(0, min(100, $rSquared * 100));

        // Determine trend
        $trend = $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable');

        return [
            'predicted_balance' => round($predictedBalance, 2),
            'confidence' => round($confidence, 2),
            'trend' => $trend,
        ];
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev($values, $mean)
    {
        $variance = 0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }
        $variance /= count($values);
        return sqrt($variance);
    }

    /**
     * Authorize organisation access
     */
    private function authorizeOrganisation(Organisation $organisation)
    {
        $user = Auth::user();

        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            abort(403, 'Unauthorized access to this organisation');
        }
    }
}
