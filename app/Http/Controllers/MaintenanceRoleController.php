<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRoleController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'name');
        $dir = $request->get('direction', 'asc');
        if (!in_array($sort, ['id', 'name', 'created_at'])) { $sort = 'name'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'asc'; }

        $maintenanceRoles = MaintenanceRole::when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('maintenance_roles.index', compact('maintenanceRoles', 'search', 'sort', 'dir'));
    }

    public function create(): View
    {
        return view('maintenance_roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:maintenance_roles,name',
            'description' => 'nullable|string',
        ]);

        MaintenanceRole::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('maintenance_roles.index')
            ->with('success', 'Specializzazione creata con successo!');
    }

    public function edit(MaintenanceRole $maintenanceRole): View
    {
        return view('maintenance_roles.edit', compact('maintenanceRole'));
    }

    public function update(Request $request, MaintenanceRole $maintenanceRole): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:maintenance_roles,name,' . $maintenanceRole->id,
            'description' => 'nullable|string',
        ]);

        $maintenanceRole->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('maintenance_roles.index')
            ->with('success', 'Specializzazione aggiornata con successo!');
    }

    public function destroy(MaintenanceRole $maintenanceRole): RedirectResponse
    {
        $maintenanceRole->delete();

        return redirect()->route('maintenance_roles.index')
            ->with('success', 'Specializzazione eliminata con successo!');
    }
}
