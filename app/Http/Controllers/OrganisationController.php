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

    public function index()
    {
        $user = Auth::user();
        $organisations = $user->organisations()->get();
        return view('organisations.index', compact('organisations'));
    }

    public function create()
    {
        return view('organisations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|string|max:3',
            'fiscal_year_start' => 'required|date_format:m-d',
        ]);

        $organisation = Organisation::create([
            'owner_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'currency' => $validated['currency'],
            'fiscal_year_start' => $validated['fiscal_year_start'],
        ]);

        session(['current_organisation_id' => $organisation->id]);

        return redirect()->route('dashboard')->with('success', 'Organisation created successfully');
    }

    public function edit(Organisation $organisation)
    {
        $this->authorize('update', $organisation);
        return view('organisations.edit', compact('organisation'));
    }

    public function update(Request $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|string|max:3',
            'fiscal_year_start' => 'required|date_format:m-d',
        ]);

        $organisation->update($validated);

        return redirect()->route('organisations.index')->with('success', 'Organisation updated successfully');
    }

    public function destroy(Organisation $organisation)
    {
        $this->authorize('delete', $organisation);
        $organisation->delete();
        return redirect()->route('organisations.index')->with('success', 'Organisation deleted successfully');
    }
}
