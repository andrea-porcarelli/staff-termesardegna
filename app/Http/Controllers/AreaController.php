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
    public function index() : View
    {
        $areas = Area::withCount('departments')->orderBy('created_at', 'desc')->get();
        return view('areas.index', compact('areas'));
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
