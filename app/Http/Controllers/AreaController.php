<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(Request $request) : View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'name', 'created_at'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'desc'; }

        $areas = Area::withCount('departments')
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('areas.index', compact('areas', 'search', 'sort', 'dir'));
    }

    public function create() : View
    {
        return view('areas.create');
    }

    public function store(AreaRequest $request) : RedirectResponse
    {
        Area::create([
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('areas.index')
            ->with('success', 'Area creata con successo!');
    }

    public function show(Area $area) : View
    {
        $area->load('departments.equipments');
        return view('areas.show', compact('area'));
    }

    public function edit(Area $area) : View
    {
        return view('areas.edit', compact('area'));
    }

    public function update(UpdateAreaRequest $request, Area $area) : RedirectResponse
    {
        $area->update([
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('areas.index')
            ->with('success', 'Area aggiornata con successo!');
    }

    public function destroy(Area $area) : RedirectResponse
    {
        $area->delete();

        return redirect()->route('areas.index')
            ->with('success', 'Area eliminata con successo!');
    }
}
