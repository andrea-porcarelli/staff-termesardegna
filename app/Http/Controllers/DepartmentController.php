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
    public function index() : View
    {
        $departments = Department::with('area')->orderBy('created_at', 'desc')->get();
        return view('departments.index', compact('departments'));
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
            ->with('success', 'Reparto creato con successo!');
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
            ->with('success', 'Reparto aggiornato con successo!');
    }

    public function destroy(Department $department) : RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Reparto eliminato con successo!');
    }
}
