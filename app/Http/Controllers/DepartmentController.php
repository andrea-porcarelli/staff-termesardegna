<?php

namespace App\Http\Controllers;

use App\Facades\Utils;
use App\Http\Requests\DepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request) : View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'name', 'created_at'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'desc'; }

        $departments = Department::with('area')
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('departments.index', compact('departments', 'search', 'sort', 'dir'));
    }

    public function create() : View
    {
        $areas = Area::where('active', true)->orderBy('name')->get();
        return view('departments.create', compact('areas'));
    }

    public function store(DepartmentRequest $request) : RedirectResponse
    {
        Department::create([
            'area_id' => $request->area_id,
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'Zona creata con successo!');
    }

    public function show(Department $department) : View
    {
        $department->load('area', 'equipments');
        return view('departments.show', compact('department'));
    }

    public function edit(Department $department) : View
    {
        $areas = Area::where('active', true)->orderBy('name')->get();
        return view('departments.edit', compact('department', 'areas'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department) : RedirectResponse
    {
        $department->update([
            'area_id' => $request->area_id,
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'Zona aggiornata con successo!');
    }

    public function destroy(Department $department) : RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Zona eliminata con successo!');
    }
}
