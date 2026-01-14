<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganisationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of organisations
     */
    public function index()
    {
        $user = Auth::user();
        $organisations = $user->organisations()->get();

        return view('organisations.index', [
            'organisations' => $organisations,
        ]);
    }

    /**
     * Show the form for creating a new organisation
     */
    public function create()
    {
        return view('organisations.create');
    }

    /**
     * Store a newly created organisation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'currency' => 'required|string|size:3',
            'fiscal_year_start' => 'required|date_format:m-d',
        ]);

        $user = Auth::user();

        $organisation = Organisation::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'currency' => $validated['currency'],
            'fiscal_year_start' => $validated['fiscal_year_start'],
            'is_active' => true,
        ]);

        // Set as current organisation
        session(['current_organisation_id' => $organisation->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Organisation created successfully!');
    }

    /**
     * Display the specified organisation
     */
    public function show(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        return view('organisations.show', [
            'organisation' => $organisation,
            'bankAccounts' => $organisation->bankAccounts()->get(),
            'totalBalance' => $organisation->getTotalBalance(),
            'transactionCount' => $organisation->getTransactionCount(),
            'predictionCount' => $organisation->predictions()->count(),
        ]);
    }

    /**
     * Show the form for editing the specified organisation
     */
    public function edit(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        return view('organisations.edit', [
            'organisation' => $organisation,
        ]);
    }

    /**
     * Update the specified organisation
     */
    public function update(Request $request, Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'currency' => 'required|string|size:3',
            'fiscal_year_start' => 'required|date_format:m-d',
            'is_active' => 'boolean',
        ]);

        $organisation->update($validated);

        return redirect()->route('organisations.show', $organisation)
            ->with('success', 'Organisation updated successfully!');
    }

    /**
     * Remove the specified organisation
     */
    public function destroy(Organisation $organisation)
    {
        $this->authorizeOrganisation($organisation);

        $organisation->delete();

        return redirect()->route('organisations.index')
            ->with('success', 'Organisation deleted successfully!');
    }

    /**
     * Switch current organisation
     */
    public function switchOrganisation(Request $request)
    {
        $orgId = $request->input('organisation_id');
        $user = Auth::user();

        $organisation = Organisation::findOrFail($orgId);

        // Check access
        if ($organisation->owner_id !== $user->id && !$user->memberOrganisations()->where('organisation_id', $orgId)->exists()) {
            abort(403, 'Unauthorized access to this organisation');
        }

        session(['current_organisation_id' => $orgId]);

        return redirect()->route('dashboard');
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
