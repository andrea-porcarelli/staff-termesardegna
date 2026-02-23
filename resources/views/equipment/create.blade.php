@extends('layouts.app')

@section('title', 'Nuovo Impianto/Macchina - Rapportini')

@section('page-title', 'Nuovo Impianto/Macchina')

@section('content')
<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-plus-circle me-2"></i>Aggiungi Nuovo Impianto/Macchina</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('equipments.store') }}" method="POST">
            @csrf

            @livewire('area-department-selector', ['areaId' => old('area_id'), 'departmentId' => old('department_id')])

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nome Impianto/Macchina <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Codice </label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                           id="code" name="code" value="{{ old('code') }}">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="manufacturer" class="form-label">Produttore</label>
                    <input type="text" class="form-control @error('manufacturer') is-invalid @enderror"
                           id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}">
                    @error('manufacturer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="model" class="form-label">Modello</label>
                    <input type="text" class="form-control @error('model') is-invalid @enderror"
                           id="model" name="model" value="{{ old('model') }}">
                    @error('model')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="serial_number" class="form-label">Numero di Serie</label>
                    <input type="text" class="form-control @error('serial_number') is-invalid @enderror"
                           id="serial_number" name="serial_number" value="{{ old('serial_number') }}">
                    @error('serial_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="installation_date" class="form-label">Data Installazione</label>
                    <input type="date" class="form-control @error('installation_date') is-invalid @enderror"
                           id="installation_date" name="installation_date" value="{{ old('installation_date') }}">
                    @error('installation_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="maintenance_frequency_days" class="form-label">Frequenza Manutenzione (giorni) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('maintenance_frequency_days') is-invalid @enderror"
                           id="maintenance_frequency_days" name="maintenance_frequency_days"
                           value="{{ old('maintenance_frequency_days', 30) }}" min="1" required>
                    @error('maintenance_frequency_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="last_maintenance_date" class="form-label">Ultima Manutenzione</label>
                    <input type="date" class="form-control @error('last_maintenance_date') is-invalid @enderror"
                           id="last_maintenance_date" name="last_maintenance_date" value="{{ old('last_maintenance_date') }}">
                    @error('last_maintenance_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">La prossima manutenzione sarà calcolata automaticamente</small>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="active" name="active"
                           {{ old('active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">
                        Attivo
                    </label>
                </div>
            </div>

            {{-- Componenti dell'impianto --}}
            <hr class="my-4">
            <div x-data="componentsManager({{ json_encode(old('components', [])) }})">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="bi bi-list-task me-2"></i>Componenti dell'Impianto</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addComponent">
                        <i class="bi bi-plus-circle me-1"></i>Aggiungi Componente
                    </button>
                </div>

                <template x-if="components.length === 0">
                    <p class="text-muted">Nessun componente aggiunto. Clicca "Aggiungi Componente" per iniziare.</p>
                </template>

                <template x-for="(comp, index) in components" :key="index">
                    <div class="card mb-3 border-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <strong x-text="'Componente ' + (index + 1)"></strong>
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeComponent(index)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm"
                                           :name="'components[' + index + '][name]'"
                                           x-model="comp.name" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Tipo Manutenzione</label>
                                    <select class="form-select form-select-sm"
                                            :name="'components[' + index + '][maintenance_type]'"
                                            x-model="comp.maintenance_type">
                                        <option value="frequency">Frequenza (giorni)</option>
                                        <option value="fixed_date">Data Fissa</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2" x-show="comp.maintenance_type === 'frequency'">
                                    <label class="form-label">Frequenza (giorni)</label>
                                    <input type="number" class="form-control form-control-sm"
                                           :name="'components[' + index + '][frequency_days]'"
                                           x-model="comp.frequency_days" min="1">
                                </div>
                                <div class="col-md-4 mb-2" x-show="comp.maintenance_type === 'fixed_date'">
                                    <label class="form-label">Prossima Data Manutenzione</label>
                                    <input type="date" class="form-control form-control-sm"
                                           :name="'components[' + index + '][next_maintenance_date]'"
                                           x-model="comp.next_maintenance_date">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Descrizione</label>
                                <textarea class="form-control form-control-sm"
                                          :name="'components[' + index + '][description]'"
                                          x-model="comp.description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-check-circle me-2"></i>Salva Impianto/Macchina
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
function componentsManager(initialComponents) {
    return {
        components: initialComponents && initialComponents.length > 0
            ? initialComponents
            : [],
        addComponent() {
            this.components.push({
                name: '',
                description: '',
                maintenance_type: 'frequency',
                frequency_days: 30,
                next_maintenance_date: '',
            });
        },
        removeComponent(index) {
            this.components.splice(index, 1);
        }
    };
}
</script>
@endpush
@endsection
