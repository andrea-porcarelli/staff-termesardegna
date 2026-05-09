@extends('layouts.app')

@section('title', 'Modifica Impianto/Macchina - Rapportini')

@section('page-title', 'Modifica Impianto/Macchina')

@php
    // Stato iniziale di situazione/data: se c'è last_maintenance_date l'impianto è "già" manutenzionato.
    $initSituation = old('maintenance_situation', $equipment->last_maintenance_date ? 'gia' : 'mai');
    $initFirstDate = old('first_intervention_date',
        $equipment->last_maintenance_date ? '' : optional($equipment->next_maintenance_date)->format('Y-m-d'));
    $initLastDate = old('last_maintenance_date', optional($equipment->last_maintenance_date)->format('Y-m-d'));
    // Se la next_maintenance_date salvata diverge dal calcolo automatico (last + freq), considerala override manuale.
    $autoNext = $equipment->last_maintenance_date && $equipment->maintenance_frequency_days
        ? $equipment->last_maintenance_date->copy()->addDays($equipment->maintenance_frequency_days)->format('Y-m-d')
        : null;
    $savedNext = optional($equipment->next_maintenance_date)->format('Y-m-d');
    $initManualOverride = old('next_maintenance_date',
        ($initSituation === 'gia' && $savedNext && $savedNext !== $autoNext) ? $savedNext : ''
    );

    $existingComponents = old('components', $equipment->components->map(function ($c) {
        $autoCompNext = $c->last_maintenance_date && $c->frequency_days
            ? $c->last_maintenance_date->copy()->addDays($c->frequency_days)->format('Y-m-d')
            : null;
        $compSavedNext = optional($c->next_maintenance_date)->format('Y-m-d');
        $sit = $c->last_maintenance_date ? 'gia' : 'mai';

        return [
            'id' => $c->id,
            'name' => $c->name,
            'frequency_days' => $c->frequency_days,
            'assignment_type' => $c->assignment_type ?? 'specializzazione',
            'maintenance_role_id' => $c->maintenance_role_id,
            'assigned_user_id' => $c->assigned_user_id,
            'intervention_title' => $c->intervention_title,
            'intervention_description' => $c->intervention_description,
            'maintenance_situation' => $sit,
            'first_intervention_date' => $c->last_maintenance_date ? '' : $compSavedNext,
            'last_maintenance_date' => optional($c->last_maintenance_date)->format('Y-m-d'),
            'next_maintenance_date' => ($sit === 'gia' && $compSavedNext && $compSavedNext !== $autoCompNext)
                ? $compSavedNext : '',
            'description' => $c->description ?? '',
        ];
    })->toArray());
@endphp

@section('content')
<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-pencil me-2"></i>Modifica Impianto/Macchina: {{ $equipment->name }}</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('equipments.update', $equipment) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- ═══════════════════════════════════════════════════════════════
                 1. ANAGRAFICA
                 ═══════════════════════════════════════════════════════════════ --}}
            <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Anagrafica</h5>

            @livewire('area-department-selector', [
                'areaId' => old('area_id', $equipment->department->area_id),
                'departmentId' => old('department_id', $equipment->department_id)
            ])

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nome Impianto/Macchina <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name', $equipment->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Codice</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                           id="code" name="code" value="{{ old('code', $equipment->code) }}">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description', $equipment->description) }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="manufacturer" class="form-label">Produttore</label>
                    <input type="text" class="form-control"
                           id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $equipment->manufacturer) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="model" class="form-label">Modello</label>
                    <input type="text" class="form-control"
                           id="model" name="model" value="{{ old('model', $equipment->model) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="serial_number" class="form-label">N° Serie</label>
                    <input type="text" class="form-control"
                           id="serial_number" name="serial_number" value="{{ old('serial_number', $equipment->serial_number) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="installation_date" class="form-label">Data Installazione</label>
                    <input type="date" class="form-control"
                           id="installation_date" name="installation_date"
                           value="{{ old('installation_date', $equipment->installation_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active"
                               {{ old('active', $equipment->active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Attivo</label>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 2. PIANIFICAZIONE MANUTENZIONE
                 ═══════════════════════════════════════════════════════════════ --}}
            <hr class="my-4">
            <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Pianificazione Manutenzione</h5>

            <div x-data="planningSection({
                assignmentType: '{{ old('assignment_type', $equipment->assignment_type ?? 'specializzazione') }}',
                situation: '{{ $initSituation }}',
                frequency: {{ old('maintenance_frequency_days', $equipment->maintenance_frequency_days ?? 30) }},
                lastDate: '{{ $initLastDate }}',
                firstDate: '{{ $initFirstDate }}',
                manualOverride: '{{ $initManualOverride }}',
            })">
                <div class="mb-3">
                    <label class="form-label">Assegnazione <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="assignment_type" id="assign_spec" value="specializzazione" x-model="assignmentType">
                        <label class="btn btn-outline-primary" for="assign_spec">
                            <i class="bi bi-tools me-1"></i>Per specializzazione
                        </label>
                        <input type="radio" class="btn-check" name="assignment_type" id="assign_dir" value="diretto" x-model="assignmentType">
                        <label class="btn btn-outline-primary" for="assign_dir">
                            <i class="bi bi-person-check me-1"></i>Tecnico diretto
                        </label>
                    </div>
                    @error('assignment_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" x-show="assignmentType === 'specializzazione'">
                        <label for="maintenance_role_id" class="form-label">Specializzazione <span class="text-danger">*</span></label>
                        <select name="maintenance_role_id" id="maintenance_role_id"
                                class="form-select @error('maintenance_role_id') is-invalid @enderror">
                            <option value="">— Seleziona specializzazione —</option>
                            @foreach($maintenanceRoles as $role)
                                <option value="{{ $role->id }}" {{ old('maintenance_role_id', $equipment->maintenance_role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('maintenance_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3" x-show="assignmentType === 'diretto'">
                        <label for="assigned_user_id" class="form-label">Manutentore <span class="text-danger">*</span></label>
                        <select name="assigned_user_id" id="assigned_user_id"
                                class="form-select @error('assigned_user_id') is-invalid @enderror">
                            <option value="">— Seleziona manutentore —</option>
                            @foreach($manutentori as $u)
                                <option value="{{ $u->id }}" {{ old('assigned_user_id', $equipment->assigned_user_id) == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="maintenance_frequency_days" class="form-label">Frequenza Manutenzione (giorni) <span class="text-danger">*</span></label>
                        <input type="number" min="1" required
                               class="form-control @error('maintenance_frequency_days') is-invalid @enderror"
                               id="maintenance_frequency_days" name="maintenance_frequency_days"
                               x-model.number="frequency">
                        @error('maintenance_frequency_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="intervention_title" class="form-label">Titolo intervento <span class="text-danger">*</span></label>
                    <input type="text" maxlength="255" required
                           class="form-control @error('intervention_title') is-invalid @enderror"
                           id="intervention_title" name="intervention_title"
                           value="{{ old('intervention_title', $equipment->intervention_title) }}">
                    @error('intervention_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="intervention_description" class="form-label">Descrizione / Cosa fare</label>
                    <textarea rows="3" class="form-control @error('intervention_description') is-invalid @enderror"
                              id="intervention_description" name="intervention_description">{{ old('intervention_description', $equipment->intervention_description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Situazione impianto <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="maintenance_situation" id="sit_mai" value="mai" x-model="situation">
                        <label class="btn btn-outline-success" for="sit_mai">🆕 Mai manutenzionato</label>
                        <input type="radio" class="btn-check" name="maintenance_situation" id="sit_gia" value="gia" x-model="situation">
                        <label class="btn btn-outline-success" for="sit_gia">🔄 Già manutenzionato</label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" x-show="situation === 'mai'">
                        <label for="first_intervention_date" class="form-label">Data primo intervento <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('first_intervention_date') is-invalid @enderror"
                               id="first_intervention_date" name="first_intervention_date"
                               x-model="firstDate">
                        @error('first_intervention_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3" x-show="situation === 'gia'">
                        <label for="last_maintenance_date" class="form-label">Ultima manutenzione eseguita <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('last_maintenance_date') is-invalid @enderror"
                               id="last_maintenance_date" name="last_maintenance_date"
                               x-model="lastDate">
                        @error('last_maintenance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3" x-show="situation === 'gia'">
                        <label class="form-label">Prossima manutenzione</label>
                        <template x-if="!manualOverrideEnabled">
                            <div>
                                <input type="text" class="form-control"
                                       :value="computedNextDate || '—'"
                                       readonly
                                       style="background-color: #d4edda; color: #155724; font-weight: 600;">
                                <a href="#" class="small mt-1 d-inline-block"
                                   @click.prevent="enableManualOverride()">
                                    <i class="bi bi-pencil"></i> Modifica manualmente
                                </a>
                            </div>
                        </template>
                        <template x-if="manualOverrideEnabled">
                            <div>
                                <input type="date" class="form-control border-warning"
                                       name="next_maintenance_date"
                                       x-model="manualOverride">
                                <small class="text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Data calcolata automaticamente sovrascritta.
                                </small>
                                <a href="#" class="small mt-1 d-inline-block"
                                   @click.prevent="disableManualOverride()">
                                    <i class="bi bi-x"></i> Ripristina calcolo automatico
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 3. COMPONENTI DELL'IMPIANTO
                 ═══════════════════════════════════════════════════════════════ --}}
            <hr class="my-4">
            <div x-data="componentsManager({{ json_encode($existingComponents) }}, @json($maintenanceRoles), @json($manutentori))">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Componenti dell'Impianto</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addComponent">
                        <i class="bi bi-plus-circle me-1"></i>Aggiungi componente
                    </button>
                </div>

                <template x-if="components.length === 0">
                    <p class="text-muted">Nessun componente aggiunto.</p>
                </template>

                <template x-for="(comp, index) in components" :key="comp.id || ('new_' + index)">
                    <div class="card mb-3 border-secondary">
                        <div class="card-body">
                            <input type="hidden" :name="'components[' + index + '][id]'" :value="comp.id || ''">

                            <div class="d-flex justify-content-between mb-2">
                                <strong x-text="'Componente ' + (index + 1)"></strong>
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeComponent(index)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Nome componente <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm"
                                           :name="'components[' + index + '][name]'"
                                           x-model="comp.name" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Frequenza (giorni) <span class="text-danger">*</span></label>
                                    <input type="number" min="1" required
                                           class="form-control form-control-sm"
                                           :name="'components[' + index + '][frequency_days]'"
                                           x-model.number="comp.frequency_days">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Assegnazione <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check"
                                           :name="'components[' + index + '][assignment_type]'"
                                           :id="'comp_assign_spec_' + index"
                                           value="specializzazione" x-model="comp.assignment_type">
                                    <label class="btn btn-outline-primary" :for="'comp_assign_spec_' + index">Per specializzazione</label>
                                    <input type="radio" class="btn-check"
                                           :name="'components[' + index + '][assignment_type]'"
                                           :id="'comp_assign_dir_' + index"
                                           value="diretto" x-model="comp.assignment_type">
                                    <label class="btn btn-outline-primary" :for="'comp_assign_dir_' + index">Tecnico diretto</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-2" x-show="comp.assignment_type === 'specializzazione'">
                                    <label class="form-label">Specializzazione <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm"
                                            :name="'components[' + index + '][maintenance_role_id]'"
                                            x-model="comp.maintenance_role_id">
                                        <option value="">— Seleziona —</option>
                                        <template x-for="role in maintenanceRoles" :key="role.id">
                                            <option :value="role.id" x-text="role.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2" x-show="comp.assignment_type === 'diretto'">
                                    <label class="form-label">Manutentore <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm"
                                            :name="'components[' + index + '][assigned_user_id]'"
                                            x-model="comp.assigned_user_id">
                                        <option value="">— Seleziona —</option>
                                        <template x-for="u in manutentori" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Titolo intervento</label>
                                <input type="text" class="form-control form-control-sm"
                                       :name="'components[' + index + '][intervention_title]'"
                                       x-model="comp.intervention_title">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Descrizione / Cosa fare</label>
                                <textarea rows="2" class="form-control form-control-sm"
                                          :name="'components[' + index + '][intervention_description]'"
                                          x-model="comp.intervention_description"></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Situazione componente <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check"
                                           :name="'components[' + index + '][maintenance_situation]'"
                                           :id="'comp_sit_mai_' + index"
                                           value="mai" x-model="comp.maintenance_situation">
                                    <label class="btn btn-outline-success" :for="'comp_sit_mai_' + index">🆕 Mai manutenzionato</label>
                                    <input type="radio" class="btn-check"
                                           :name="'components[' + index + '][maintenance_situation]'"
                                           :id="'comp_sit_gia_' + index"
                                           value="gia" x-model="comp.maintenance_situation">
                                    <label class="btn btn-outline-success" :for="'comp_sit_gia_' + index">🔄 Già manutenzionato</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-2" x-show="comp.maintenance_situation === 'mai'">
                                    <label class="form-label">Data primo intervento <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm"
                                           :name="'components[' + index + '][first_intervention_date]'"
                                           x-model="comp.first_intervention_date">
                                </div>
                                <div class="col-md-6 mb-2" x-show="comp.maintenance_situation === 'gia'">
                                    <label class="form-label">Ultima manutenzione <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm"
                                           :name="'components[' + index + '][last_maintenance_date]'"
                                           x-model="comp.last_maintenance_date">
                                </div>
                                <div class="col-md-6 mb-2" x-show="comp.maintenance_situation === 'gia'">
                                    <label class="form-label">Prossima manutenzione</label>
                                    <template x-if="!comp.manual_override_enabled">
                                        <div>
                                            <input type="text" class="form-control form-control-sm"
                                                   :value="computeNextDate(comp) || '—'"
                                                   readonly
                                                   style="background-color: #d4edda; color: #155724; font-weight: 600;">
                                            <a href="#" class="small"
                                               @click.prevent="comp.manual_override_enabled = true">
                                                <i class="bi bi-pencil"></i> Modifica manualmente
                                            </a>
                                        </div>
                                    </template>
                                    <template x-if="comp.manual_override_enabled">
                                        <div>
                                            <input type="date" class="form-control form-control-sm border-warning"
                                                   :name="'components[' + index + '][next_maintenance_date]'"
                                                   x-model="comp.next_maintenance_date">
                                            <a href="#" class="small text-warning"
                                               @click.prevent="comp.manual_override_enabled = false; comp.next_maintenance_date = ''">
                                                <i class="bi bi-x"></i> Ripristina calcolo automatico
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-check-circle me-2"></i>Aggiorna Impianto/Macchina
                </button>
                <a href="{{ route('equipments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function planningSection(initial) {
    return {
        assignmentType: initial.assignmentType || 'specializzazione',
        situation: initial.situation || 'mai',
        frequency: initial.frequency || 30,
        lastDate: initial.lastDate || '',
        firstDate: initial.firstDate || '',
        manualOverride: initial.manualOverride || '',
        manualOverrideEnabled: !!initial.manualOverride,

        get computedNextDate() {
            if (!this.lastDate || !this.frequency) return '';
            const d = new Date(this.lastDate);
            if (isNaN(d)) return '';
            d.setDate(d.getDate() + parseInt(this.frequency, 10));
            return d.toISOString().slice(0, 10);
        },

        enableManualOverride() {
            this.manualOverrideEnabled = true;
            if (!this.manualOverride) {
                this.manualOverride = this.computedNextDate;
            }
        },
        disableManualOverride() {
            this.manualOverrideEnabled = false;
            this.manualOverride = '';
        },
    };
}

function componentsManager(initial, maintenanceRoles, manutentori) {
    return {
        components: (initial && initial.length > 0)
            ? initial.map(c => ({ ...defaultComponent(), ...c, manual_override_enabled: !!c.next_maintenance_date }))
            : [],
        maintenanceRoles: maintenanceRoles || [],
        manutentori: manutentori || [],

        addComponent() {
            this.components.push(defaultComponent());
        },
        removeComponent(index) {
            this.components.splice(index, 1);
        },
        computeNextDate(comp) {
            if (!comp.last_maintenance_date || !comp.frequency_days) return '';
            const d = new Date(comp.last_maintenance_date);
            if (isNaN(d)) return '';
            d.setDate(d.getDate() + parseInt(comp.frequency_days, 10));
            return d.toISOString().slice(0, 10);
        },
    };
}

function defaultComponent() {
    return {
        id: '',
        name: '',
        frequency_days: 30,
        assignment_type: 'specializzazione',
        maintenance_role_id: '',
        assigned_user_id: '',
        intervention_title: '',
        intervention_description: '',
        maintenance_situation: 'mai',
        first_intervention_date: '',
        last_maintenance_date: '',
        next_maintenance_date: '',
        description: '',
        manual_override_enabled: false,
    };
}
</script>
@endpush
@endsection
