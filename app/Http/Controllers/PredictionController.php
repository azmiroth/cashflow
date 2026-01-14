<?php
/**
 * Prediction Controller
 * Version: 1.0
 * Created: 2026-01-13 GMT+11
 */

namespace App\Http\Controllers;

use App\Models\CashFlowPrediction;
use App\Models\Organisation;
use App\Services\PredictionEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredictionController extends Controller
{
    protected $predictionEngine;

    public function __construct(PredictionEngineService $predictionEngine)
    {
        $this->middleware('auth');
        $this->predictionEngine = $predictionEngine;
    }

    public function index()
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        $predictions = $organisation->predictions()->latest()->paginate(15);
        return view('predictions.index', compact('organisation', 'predictions'));
    }

    public function create()
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        $bankAccounts = $organisation->bankAccounts()->where('is_active', true)->get();
        return view('predictions.create', compact('organisation', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $orgId = session('current_organisation_id');
        $organisation = Organisation::findOrFail($orgId);
        $this->authorize('view', $organisation);

        $validated = $request->validate([
            'prediction_name' => 'required|string|max:255',
            'bank_accounts' => 'required|array|min:1',
            'bank_accounts.*' => 'exists:bank_accounts,id',
            'analysis_period_days' => 'required|integer|in:30,60,90,180,365',
            'forecast_period_days' => 'required|integer|in:7,14,30,60,90',
            'prediction_method' => 'required|in:moving_average,trend_analysis',
        ]);

        $bankAccountIds = $validated['bank_accounts'];

        // Generate prediction
        if ($validated['prediction_method'] === 'moving_average') {
            $result = $this->predictionEngine->predictMovingAverage(
                $bankAccountIds,
                $validated['analysis_period_days'],
                $validated['forecast_period_days']
            );
        } else {
            $result = $this->predictionEngine->predictTrendAnalysis(
                $bankAccountIds,
                $validated['analysis_period_days'],
                $validated['forecast_period_days']
            );
        }

        // Create prediction record
        $prediction = $organisation->predictions()->create([
            'prediction_name' => $validated['prediction_name'],
            'analysis_period_days' => $validated['analysis_period_days'],
            'forecast_period_days' => $validated['forecast_period_days'],
            'prediction_method' => $validated['prediction_method'],
            'predicted_balance' => $result['predicted_balance'],
            'confidence_level' => $result['confidence_level'],
            'trend' => $result['trend'],
            'created_by' => Auth::id(),
        ]);

        // Attach bank accounts
        $prediction->bankAccounts()->attach($bankAccountIds);

        return redirect()->route('predictions.index')->with('success', 'Prediction created successfully');
    }

    public function show(CashFlowPrediction $prediction)
    {
        $this->authorize('view', $prediction->organisation);
        $bankAccounts = $prediction->bankAccounts()->get();
        return view('predictions.show', compact('prediction', 'bankAccounts'));
    }

    public function destroy(CashFlowPrediction $prediction)
    {
        $this->authorize('view', $prediction->organisation);
        $prediction->delete();
        return redirect()->route('predictions.index')->with('success', 'Prediction deleted successfully');
    }
}
