<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'name');
        $dir = $request->get('direction', 'asc');
        if (!in_array($sort, ['id', 'name', 'created_at'])) { $sort = 'name'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'asc'; }

        $teams = Team::when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('teams.index', compact('teams', 'search', 'sort', 'dir'));
    }

    public function create(): View
    {
        return view('teams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'description' => 'nullable|string',
        ]);

        Team::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('teams.index')
            ->with('success', 'Team creato con successo!');
    }

    public function edit(Team $team): View
    {
        return view('teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string',
        ]);

        $team->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('teams.index')
            ->with('success', 'Team aggiornato con successo!');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team eliminato con successo!');
    }
}
