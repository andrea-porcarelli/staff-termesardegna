<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request) : View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('direction', 'desc');
        $allowedSorts = ['code', 'name', 'created_at'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'desc'; }

        $equipment = Equipment::with(['department.area'])
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere('code', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('equipment.index', compact('equipment', 'search', 'sort', 'dir'));
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
            'installation_date' => $request->filled('installation_date') ? $request->installation_date : null,
            'maintenance_frequency_days' => $request->maintenance_frequency_days,
            'last_maintenance_date' => $request->filled('last_maintenance_date') ? $request->last_maintenance_date : null,
            'active' => $request->boolean('active'),
        ];

        if ($request->filled('last_maintenance_date') && $request->filled('maintenance_frequency_days')) {
            $data['next_maintenance_date'] = Carbon::parse($request->last_maintenance_date)
                ->addDays((int) $request->maintenance_frequency_days);
        }

        $equipment = Equipment::create($data);

        // Salva componenti
        foreach ($request->get('components', []) as $comp) {
            if (!empty($comp['name'])) {
                $equipment->components()->create([
                    'name' => $comp['name'],
                    'description' => $comp['description'] ?? null,
                    'maintenance_type' => $comp['maintenance_type'] ?? 'frequency',
                    'frequency_days' => !empty($comp['frequency_days']) ? (int)$comp['frequency_days'] : null,
                    'next_maintenance_date' => !empty($comp['next_maintenance_date']) ? $comp['next_maintenance_date'] : null,
                ]);
            }
        }

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina creato con successo!');
    }

    public function show(Equipment $equipment) : View
    {
        $equipment->load('department.area', 'components');
        return view('equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment) : View
    {
        $areas = Area::where('active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $equipment->load('components');
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
            'installation_date' => $request->filled('installation_date') ? $request->installation_date : null,
            'maintenance_frequency_days' => $request->maintenance_frequency_days,
            'last_maintenance_date' => $request->filled('last_maintenance_date') ? $request->last_maintenance_date : null,
            'active' => $request->boolean('active'),
        ];

        if ($request->filled('last_maintenance_date') && $request->filled('maintenance_frequency_days')) {
            $data['next_maintenance_date'] = Carbon::parse($request->last_maintenance_date)
                ->addDays((int) $request->maintenance_frequency_days);
        }

        $equipment->update($data);

        // Aggiorna componenti: elimina esistenti e ricrea
        $equipment->components()->delete();
        foreach ($request->get('components', []) as $comp) {
            if (!empty($comp['name'])) {
                $equipment->components()->create([
                    'name' => $comp['name'],
                    'description' => $comp['description'] ?? null,
                    'maintenance_type' => $comp['maintenance_type'] ?? 'frequency',
                    'frequency_days' => !empty($comp['frequency_days']) ? (int)$comp['frequency_days'] : null,
                    'next_maintenance_date' => !empty($comp['next_maintenance_date']) ? $comp['next_maintenance_date'] : null,
                ]);
            }
        }

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina aggiornato con successo!');
    }

    public function destroy(Equipment $equipment) : RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina eliminato con successo!');
    }
}
