<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index() : View
    {
        $equipment = Equipment::with(['department.area'])->orderBy('created_at', 'desc')->get();
        return view('equipment.index', compact('equipment'));
    }

    public function create()  : View
    {
        $areas = Area::where('active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        return view('equipment.create', compact('areas', 'departments'));
    }

    public function store(EquipmentRequest $request) : RedirectResponse
    {
        $data = [
            'department_id' => $request->department_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->installation_date,
            'maintenance_frequency_days' => $request->maintenance_frequency_days,
            'last_maintenance_date' => $request->last_maintenance_date,
            'active' => $request->boolean('active'),
        ];

        if ($request->last_maintenance_date) {
            $data['next_maintenance_date'] = Carbon::parse($request->last_maintenance_date)
                ->addDays($request->maintenance_frequency_days);
        }

        Equipment::create($data);

        return redirect()->route('equipments.index')
            ->with('success', 'Apparato creato con successo!');
    }

    public function show(Equipment $equipment) : View
    {
        $equipment->load('department.area');
        return view('equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment) : View
    {
        $areas = Area::where('active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        return view('equipment.edit', compact('equipment', 'areas', 'departments'));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment) : RedirectResponse
    {
        $data = [
            'department_id' => $request->department_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->installation_date,
            'maintenance_frequency_days' => $request->maintenance_frequency_days,
            'last_maintenance_date' => $request->last_maintenance_date,
            'active' => $request->boolean('active'),
        ];

        if ($request->last_maintenance_date) {
            $data['next_maintenance_date'] = Carbon::parse($request->last_maintenance_date)
                ->addDays($request->maintenance_frequency_days);
        }

        $equipment->update($data);

        return redirect()->route('equipments.index')
            ->with('success', 'Apparato aggiornato con successo!');
    }

    public function destroy(Equipment $equipment) : RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('equipments.index')
            ->with('success', 'Apparato eliminato con successo!');
    }
}
