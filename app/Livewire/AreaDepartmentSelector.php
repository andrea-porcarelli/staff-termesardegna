<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Area;
use App\Models\Department;

class AreaDepartmentSelector extends Component
{
    public $areas;
    public $departments;
    public $selectedAreaId;
    public $selectedDepartmentId;

    public $showNewAreaForm = false;
    public $showNewDepartmentForm = false;

    public $newAreaName = '';
    public $newDepartmentName = '';

    public function mount($areaId = null, $departmentId = null)
    {
        $this->selectedAreaId = $areaId;
        $this->selectedDepartmentId = $departmentId;
        $this->loadAreas();
        $this->loadDepartments();
    }

    public function loadAreas()
    {
        $this->areas = Area::where('active', true)->orderBy('name')->get();
    }

    public function loadDepartments()
    {
        if ($this->selectedAreaId) {
            $this->departments = Department::where('area_id', $this->selectedAreaId)
                ->where('active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->departments = collect();
        }
    }

    public function updatedSelectedAreaId()
    {
        $this->selectedDepartmentId = null;
        $this->loadDepartments();
        $this->dispatch('areaChanged', $this->selectedAreaId);
    }

    public function updatedSelectedDepartmentId()
    {
        $this->dispatch('departmentChanged', $this->selectedDepartmentId);
    }

    public function toggleNewAreaForm()
    {
        $this->showNewAreaForm = !$this->showNewAreaForm;
        $this->showNewDepartmentForm = false;
        $this->resetNewAreaForm();
    }

    public function toggleNewDepartmentForm()
    {
        if (!$this->selectedAreaId) {
            session()->flash('error', 'Seleziona prima un\'area');
            return;
        }
        $this->showNewDepartmentForm = !$this->showNewDepartmentForm;
        $this->showNewAreaForm = false;
        $this->resetNewDepartmentForm();
    }

    public function saveNewArea()
    {
        $this->validate([
            'newAreaName' => 'required|string|max:255',
        ], [
            'newAreaName.required' => 'Il nome dell\'area è obbligatorio',
            'newAreaName.max' => 'Il nome non può superare i 255 caratteri',
        ]);

        $area = Area::create([
            'name' => $this->newAreaName,
            'active' => true,
        ]);

        $this->loadAreas();
        $this->selectedAreaId = $area->id;
        $this->showNewAreaForm = false;
        $this->resetNewAreaForm();
        $this->loadDepartments();

        session()->flash('success', 'Area creata con successo!');
        $this->dispatch('areaChanged', $this->selectedAreaId);
    }

    public function saveNewDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255',
        ], [
            'newDepartmentName.required' => 'Il nome del reparto è obbligatorio',
            'newDepartmentName.max' => 'Il nome non può superare i 255 caratteri',
        ]);

        $department = Department::create([
            'area_id' => $this->selectedAreaId,
            'name' => $this->newDepartmentName,
            'active' => true,
        ]);

        $this->loadDepartments();
        $this->selectedDepartmentId = $department->id;
        $this->showNewDepartmentForm = false;
        $this->resetNewDepartmentForm();

        session()->flash('success', 'Zona creata con successo!');
        $this->dispatch('departmentChanged', $this->selectedDepartmentId);
    }

    public function resetNewAreaForm()
    {
        $this->newAreaName = '';
    }

    public function resetNewDepartmentForm()
    {
        $this->newDepartmentName = '';
    }

    public function render()
    {
        return view('livewire.area-department-selector');
    }
}
