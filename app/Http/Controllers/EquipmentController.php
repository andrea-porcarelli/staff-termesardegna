<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Area;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\MaintenanceRole;
use App\Models\Media;
use App\Models\User;
use App\Services\PlannedInterventionFactory;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('direction', 'desc');
        $allowedSorts = ['code', 'name', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        if (! in_array($dir, ['asc', 'desc'])) {
            $dir = 'desc';
        }

        $equipment = Equipment::with(['department.area'])
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere('code', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('equipment.index', compact('equipment', 'search', 'sort', 'dir'));
    }

    public function create(): View
    {
        $areas = Area::where('active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $manutentori = User::where('role', 'manutentore')->where('active', true)->orderBy('name')->get();

        // ID temporaneo stabile per gli allegati dell'impianto (vive quanto la
        // sessione): al submit del form vengono ri-parentizzati sul vero Equipment.
        $tempMediaId = old('temp_media_id') ?: 'temp_'.uniqid().'_'.Auth::id();

        // Dopo un fallimento di validazione, recupera i Media già caricati come
        // TempMedia così da ripopolare il widget allegati.
        $equipmentInitialMedia = $this->serializeMediaForTemp($tempMediaId);
        $componentsInitialMedia = collect(old('components', []))
            ->mapWithKeys(function ($c) {
                $tid = $c['temp_media_id'] ?? null;

                return $tid ? [$tid => $this->serializeMediaForTemp($tid)] : [];
            })->toArray();

        return view('equipment.create', compact(
            'areas', 'departments', 'maintenanceRoles', 'manutentori',
            'tempMediaId', 'equipmentInitialMedia', 'componentsInitialMedia'
        ));
    }

    public function store(EquipmentRequest $request, PlannedInterventionFactory $factory): RedirectResponse
    {
        $userId = Auth::id();
        $freq = (int) $request->maintenance_frequency_days;
        $assignType = $request->assignment_type;

        $data = [
            'department_id' => $request->department_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->filled('installation_date') ? $request->installation_date : null,
            'maintenance_frequency_days' => $freq,
            'last_maintenance_date' => $request->maintenance_situation === 'gia'
                ? $request->last_maintenance_date
                : null,
            'next_maintenance_date' => $this->resolveNextDate($request->all(), $freq),
            'assignment_type' => $assignType,
            'maintenance_role_id' => $assignType === 'specializzazione' ? $request->maintenance_role_id : null,
            'assigned_user_id' => $assignType === 'diretto' ? $request->assigned_user_id : null,
            'intervention_title' => $request->intervention_title,
            'intervention_description' => $request->intervention_description,
            'active' => $request->boolean('active'),
        ];

        $equipment = Equipment::create($data);

        // Ri-parentizza gli allegati caricati prima del save (TempMedia → Equipment).
        $this->attachTempMedia($request->input('temp_media_id'), Equipment::class, $equipment->id);

        // Salva componenti e crea i loro primi ticket pianificati.
        foreach ($request->get('components', []) as $comp) {
            if (empty($comp['name'])) {
                continue;
            }

            $newComp = $this->createComponent($equipment, $comp);
            $this->attachTempMedia($comp['temp_media_id'] ?? null, EquipmentComponent::class, $newComp->id);
            $factory->createForComponent($newComp, null, $userId);
        }

        // Primo ticket pianificato per l'impianto principale.
        $factory->createForEquipment($equipment, null, $userId);

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina creato. Ticket pianificati generati automaticamente.');
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load('department.area', 'components');

        return view('equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment): View
    {
        $areas = Area::where('active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('active', true)->orderBy('name')->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $manutentori = User::where('role', 'manutentore')->where('active', true)->orderBy('name')->get();
        $equipment->load(['components', 'media']);
        $equipment->components->load('media');

        // Allegati esistenti per il widget frontend (impianto + per ciascun componente).
        $equipmentInitialMedia = $this->serializeMedia($equipment->media);
        $componentsInitialMedia = $equipment->components
            ->mapWithKeys(fn ($c) => [$c->id => $this->serializeMedia($c->media)])
            ->toArray();

        // Media per NUOVI componenti aggiunti e persi per validation failure.
        $newComponentsInitialMedia = collect(old('components', []))
            ->mapWithKeys(function ($c) {
                $tid = $c['temp_media_id'] ?? null;

                return $tid ? [$tid => $this->serializeMediaForTemp($tid)] : [];
            })->toArray();

        return view('equipment.edit', compact(
            'equipment', 'areas', 'departments', 'maintenanceRoles', 'manutentori',
            'equipmentInitialMedia', 'componentsInitialMedia', 'newComponentsInitialMedia'
        ));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment, PlannedInterventionFactory $factory): RedirectResponse
    {
        $userId = Auth::id();
        $freq = (int) $request->maintenance_frequency_days;
        $assignType = $request->assignment_type;

        $data = [
            'department_id' => $request->department_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->filled('installation_date') ? $request->installation_date : null,
            'maintenance_frequency_days' => $freq,
            'last_maintenance_date' => $request->maintenance_situation === 'gia'
                ? $request->last_maintenance_date
                : null,
            'next_maintenance_date' => $this->resolveNextDate($request->all(), $freq),
            'assignment_type' => $assignType,
            'maintenance_role_id' => $assignType === 'specializzazione' ? $request->maintenance_role_id : null,
            'assigned_user_id' => $assignType === 'diretto' ? $request->assigned_user_id : null,
            'intervention_title' => $request->intervention_title,
            'intervention_description' => $request->intervention_description,
            'active' => $request->boolean('active'),
        ];

        $equipment->update($data);

        // Upsert componenti senza distruggere lo storico: aggiorna esistenti per id,
        // crea nuovi (e genera il primo ticket), elimina quelli rimossi dal form.
        $submitted = $request->get('components', []);
        $keepIds = [];

        foreach ($submitted as $comp) {
            if (empty($comp['name'])) {
                continue;
            }

            if (! empty($comp['id'])) {
                $existing = $equipment->components()->where('id', $comp['id'])->first();
                if ($existing) {
                    $this->fillComponent($existing, $comp);
                    $existing->save();
                    $keepIds[] = $existing->id;
                    // Componente esistente: eventuale temp_media_id (improbabile) viene ri-parentizzato.
                    $this->attachTempMedia($comp['temp_media_id'] ?? null, EquipmentComponent::class, $existing->id);

                    continue;
                }
            }

            $newComp = $this->createComponent($equipment, $comp);
            $keepIds[] = $newComp->id;
            $this->attachTempMedia($comp['temp_media_id'] ?? null, EquipmentComponent::class, $newComp->id);
            $factory->createForComponent($newComp, null, $userId);
        }

        // Pulisci i media dei componenti rimossi (il cascade non c'è).
        $toDelete = $equipment->components()->whereNotIn('id', $keepIds)->get();
        foreach ($toDelete as $deadComp) {
            foreach ($deadComp->media as $m) {
                Storage::disk('public')->delete($m->file_path);
                $m->delete();
            }
            $deadComp->delete();
        }

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina aggiornato.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('equipments.index')
            ->with('success', 'Impianto/Macchina eliminato con successo!');
    }

    /**
     * Risolve la data prossima manutenzione dal payload del form.
     * Override manuale > scenario "mai" > scenario "già" (last + frequenza).
     */
    private function resolveNextDate(array $data, int $freq): ?string
    {
        if (! empty($data['next_maintenance_date'])) {
            return Carbon::parse($data['next_maintenance_date'])->toDateString();
        }

        $sit = $data['maintenance_situation'] ?? null;

        if ($sit === 'mai' && ! empty($data['first_intervention_date'])) {
            return Carbon::parse($data['first_intervention_date'])->toDateString();
        }

        if ($sit === 'gia' && ! empty($data['last_maintenance_date'])) {
            return Carbon::parse($data['last_maintenance_date'])->addDays($freq)->toDateString();
        }

        return null;
    }

    private function createComponent(Equipment $equipment, array $comp): EquipmentComponent
    {
        $component = $equipment->components()->make();
        $this->fillComponent($component, $comp);
        $component->save();

        return $component;
    }

    /**
     * Serializza i Media per il widget frontend (lista iniziale).
     *
     * @return array<int, array<string, mixed>>
     */
    private function serializeMedia($mediaCollection): array
    {
        return collect($mediaCollection)->map(fn (Media $m) => [
            'id' => $m->id,
            'file_name' => $m->file_name,
            'file_type' => $m->file_type,
            'file_size' => $m->file_size,
            'is_image' => str_contains($m->file_type, 'image'),
            'url' => Storage::url($m->file_path),
        ])->values()->all();
    }

    private function serializeMediaForTemp(string $tempId): array
    {
        return $this->serializeMedia(
            Media::where('mediable_type', 'TempMedia')->where('mediable_id', $tempId)->get()
        );
    }

    /**
     * Sposta eventuali Media caricati prima del save (mediable_type=TempMedia,
     * mediable_id=$tempId) sul vero mediable passato.
     */
    private function attachTempMedia(?string $tempId, string $mediableType, int $mediableId): void
    {
        if (empty($tempId)) {
            return;
        }

        Media::where('mediable_type', 'TempMedia')
            ->where('mediable_id', $tempId)
            ->update([
                'mediable_type' => $mediableType,
                'mediable_id' => (string) $mediableId,
            ]);
    }

    private function fillComponent(EquipmentComponent $component, array $comp): void
    {
        $freq = (int) ($comp['frequency_days'] ?? 0);
        $assignType = $comp['assignment_type'] ?? 'specializzazione';
        $sit = $comp['maintenance_situation'] ?? null;

        $component->fill([
            'name' => $comp['name'],
            'description' => $comp['description'] ?? null,
            'maintenance_type' => 'frequency',
            'frequency_days' => $freq ?: null,
            'last_maintenance_date' => $sit === 'gia' && ! empty($comp['last_maintenance_date'])
                ? $comp['last_maintenance_date']
                : null,
            'next_maintenance_date' => $this->resolveNextDate($comp, $freq),
            'assignment_type' => $assignType,
            'maintenance_role_id' => $assignType === 'specializzazione' ? ($comp['maintenance_role_id'] ?? null) : null,
            'assigned_user_id' => $assignType === 'diretto' ? ($comp['assigned_user_id'] ?? null) : null,
            'intervention_title' => $comp['intervention_title'] ?? null,
            'intervention_description' => $comp['intervention_description'] ?? null,
        ]);
    }
}
