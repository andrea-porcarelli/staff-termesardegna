@extends('layouts.app')

@section('title', 'Nuovo Ticket - Rapportini')

@section('page-title', 'Nuovo Ticket')

@section('content')
<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-plus-circle me-2"></i>Pianifica Nuovo Ticket</h4>
    </div>
    <div class="card-body">
        <script>
            function interventionForm() {
                const userRole = '{{ $userRole }}';
                const canSeePianificazione = userRole === 'admin';
                const defaultTipo = canSeePianificazione ? '{{ old('tipo', 'pianificazione') }}' : 'ordinario';
                const userAreaId = '{{ $userAreaId ?? '' }}';
                const userDepartmentIds = @json($userDepartmentIds ?? []);

                return {
                    tipo: defaultTipo,
                    userRole: userRole,
                    canSeePianificazione: canSeePianificazione,
                    canDirectAssignOrdinario: userRole === 'admin',
                    selectedAreaId: '{{ old('area_id', '') }}' || userAreaId,
                    selectedEquipmentId: '{{ old('equipment_id', '') }}',
                    assignmentType: '{{ old('assignment_type', 'specializzazione') }}',
                    selectedPriority: '{{ old('priority', 'low') }}',
                    components: @json($components),
                    departments: @json($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'area_id' => $d->area_id])),
                    userDepartmentIds: userDepartmentIds,

                    init() {
                        this.$watch('assignmentType', (val) => {
                            if (val === 'specializzazione' && this.$refs.assignedUser) {
                                this.$refs.assignedUser.value = '';
                            } else if (val === 'diretto' && this.$refs.maintenanceRole) {
                                this.$refs.maintenanceRole.value = '';
                            }
                        });
                    },

                    isAreaLocked() {
                        return this.userRole === 'manutentore';
                    },

                    isDepartmentVisible(deptId, deptAreaId) {
                        if (this.userRole === 'manutentore') {
                            return this.userDepartmentIds.includes(deptId);
                        }
                        return !this.selectedAreaId || this.selectedAreaId == deptAreaId;
                    },

                    onEquipmentChange(equipmentId) {
                        this.selectedEquipmentId = equipmentId;
                        if (!equipmentId) return;
                        var dateField = document.getElementById('scheduled_date');
                        fetch(`/api/equipments/${equipmentId}/planning`)
                            .then(r => r.json())
                            .then(data => {
                                if (data.next_maintenance_date && dateField && !dateField.value) {
                                    dateField.value = data.next_maintenance_date;
                                }
                            });
                    },

                    showMaintenanceRole() {
                        return this.assignmentType === 'specializzazione';
                    },

                    showAssignedUser() {
                        return this.assignmentType === 'diretto'
                            && (this.tipo === 'pianificazione' || this.canDirectAssignOrdinario);
                    },

                    showAssignmentToggle() {
                        return this.tipo === 'pianificazione' || this.canDirectAssignOrdinario;
                    },

                    showDateTime() {
                        return this.tipo === 'pianificazione'
                            || (this.tipo === 'ordinario' && this.selectedPriority === 'fixed_date');
                    }
                };
            }
        </script>
        <form action="{{ route('interventions.store') }}" method="POST" x-data="interventionForm()">
            @csrf

            {{-- Tipo intervento --}}
            <div class="mb-4" x-show="canSeePianificazione">
                <label class="form-label fw-semibold">Tipo ticket <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo" id="tipo_pianificazione"
                               value="pianificazione" x-model="tipo">
                        <label class="form-check-label" for="tipo_pianificazione">
                            <i class="bi bi-gear me-1"></i>Pianificazione
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo" id="tipo_ordinario"
                               value="ordinario" x-model="tipo">
                        <label class="form-check-label" for="tipo_ordinario">
                            <i class="bi bi-wrench me-1"></i>Ticket Ordinario
                        </label>
                    </div>
                </div>
                @error('tipo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <template x-if="!canSeePianificazione">
                <input type="hidden" name="tipo" :value="tipo">
            </template>

            {{-- Riga 1: Impianto + Componente (pianificazione) | Area + Zona (ordinario) --}}
            <div class="row">
                {{-- Impianto (solo pianificazione) --}}
                <div class="col-md-6 mb-3" x-show="tipo === 'pianificazione'" x-cloak>
                    <label for="equipment_id" class="form-label">Impianto/Macchina <span class="text-danger">*</span></label>
                    <select class="form-select @error('equipment_id') is-invalid @enderror"
                            id="equipment_id" name="equipment_id"
                            :required="tipo === 'pianificazione'"
                            @change="onEquipmentChange($event.target.value)">
                        <option value="">Seleziona un impianto...</option>
                        @foreach($equipments as $equipment)
                            <option value="{{ $equipment->id }}" {{ old('equipment_id') == $equipment->id ? 'selected' : '' }}>
                                {{ $equipment->name }} - {{ $equipment->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('equipment_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Componente in cascata (solo pianificazione + impianto selezionato) --}}
                <div class="col-md-6 mb-3" x-show="tipo === 'pianificazione' && selectedEquipmentId" x-cloak>
                    <label for="component_id" class="form-label">
                        Componente
                        <small class="text-muted">(opzionale — lascia vuoto per tutto l'impianto)</small>
                    </label>
                    <select class="form-select @error('component_id') is-invalid @enderror"
                            id="component_id" name="component_id">
                        <option value="">Intero impianto</option>
                        @foreach($components as $component)
                            <option value="{{ $component->id }}"
                                    x-show="selectedEquipmentId == '{{ $component->equipment_id }}'"
                                    {{ old('component_id') == $component->id ? 'selected' : '' }}>
                                {{ $component->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('component_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Area (solo ordinario) --}}
                <div class="col-md-4 mb-3" x-show="tipo === 'ordinario'" x-cloak>
                    <label for="area_id" class="form-label">Area <span class="text-danger">*</span></label>
                    <select class="form-select @error('area_id') is-invalid @enderror"
                            id="area_id" name="area_id"
                            :required="tipo === 'ordinario'"
                            :disabled="isAreaLocked()"
                            x-model="selectedAreaId">
                        <option value="">Seleziona un'area...</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Hidden field per inviare il valore quando il select è disabled --}}
                    <template x-if="isAreaLocked()">
                        <input type="hidden" name="area_id" :value="selectedAreaId">
                    </template>
                    @error('area_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Zona filtrata (solo ordinario) — opzionale: vuoto = tutta l'area --}}
                <div class="col-md-4 mb-3" x-show="tipo === 'ordinario'" x-cloak>
                    <label for="department_id" class="form-label">
                        Zona
                        <small class="text-muted">(opzionale — vuoto = tutta l'area)</small>
                    </label>
                    <select class="form-select @error('department_id') is-invalid @enderror"
                            id="department_id" name="department_id">
                        <option value="">Tutta l'area</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}"
                                    x-show="isDepartmentVisible({{ $dept->id }}, {{ $dept->area_id }})"
                                    {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Priorità (solo ordinario) --}}
                <div class="col-md-4 mb-3" x-show="tipo === 'ordinario'" x-cloak>
                    <label for="priority" class="form-label">Priorità <span class="text-danger">*</span></label>
                    <select class="form-select @error('priority') is-invalid @enderror"
                            id="priority" name="priority"
                            x-model="selectedPriority"
                            :required="tipo === 'ordinario'">
                        <option value="low"        {{ old('priority', 'low') == 'low'  ? 'selected' : '' }}>Bassa — entro 7 giorni</option>
                        <option value="high"       {{ old('priority') == 'high'       ? 'selected' : '' }}>Alta — entro 24 ore</option>
                        <option value="fixed_date" {{ old('priority') == 'fixed_date' ? 'selected' : '' }}>Data fissa — seleziona data e ora</option>
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Tipo assegnazione (pianificazione + admin sull'ordinario) --}}
            <div class="mb-3" x-show="showAssignmentToggle()" x-cloak>
                <label class="form-label fw-semibold">Assegnazione</label>
                <div class="d-flex gap-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assignment_type"
                               id="assign_specializzazione" value="specializzazione" x-model="assignmentType">
                        <label class="form-check-label" for="assign_specializzazione">
                            <i class="bi bi-person-badge me-1"></i>Per specializzazione
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assignment_type"
                               id="assign_diretto" value="diretto" x-model="assignmentType">
                        <label class="form-check-label" for="assign_diretto">
                            <i class="bi bi-person-check me-1"></i>Tecnico diretto
                        </label>
                    </div>
                </div>
            </div>

            {{-- Riga assegnazione: specializzazione (pianificazione+spec | ordinario) o tecnico diretto (pianificazione+diretto) --}}
            <div class="row">
                {{-- Specializzazione: visibile per (pianificazione+specializzazione) OPPURE ordinario --}}
                <div class="col-md-6 mb-3" x-show="showMaintenanceRole()" x-cloak>
                    <label for="maintenance_role_id" class="form-label">
                        Specializzazione
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('maintenance_role_id') is-invalid @enderror"
                            id="maintenance_role_id" name="maintenance_role_id"
                            x-ref="maintenanceRole"
                            :required="showMaintenanceRole()">
                        <option value="">Seleziona una specializzazione...</option>
                        @foreach($maintenanceRoles as $role)
                            <option value="{{ $role->id }}" {{ old('maintenance_role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('maintenance_role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Operatore/manutentore diretto: pianificazione+diretto OPPURE admin+ordinario+diretto --}}
                <div class="col-md-6 mb-3" x-show="showAssignedUser()" x-cloak>
                    <label for="assigned_user_id" class="form-label">
                        <span x-show="tipo === 'pianificazione'">Operatore Assegnato</span>
                        <span x-show="tipo === 'ordinario'" x-cloak>Manutentore Assegnato</span>
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('assigned_user_id') is-invalid @enderror"
                            id="assigned_user_id" name="assigned_user_id"
                            x-ref="assignedUser"
                            :required="showAssignedUser()">
                        <option value="">Seleziona...</option>
                        @foreach($operators as $operator)
                            <option value="{{ $operator->id }}"
                                    data-role="{{ $operator->role }}"
                                    x-show="tipo === 'pianificazione' || '{{ $operator->role }}' === 'manutentore'"
                                    {{ old('assigned_user_id') == $operator->id ? 'selected' : '' }}>
                                {{ $operator->name }}{{ $operator->role === 'manutentore' ? '' : ' (operatore)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Data/Ora: pianificazione (sempre) | ordinario con data fissa --}}
            <div class="row" x-show="showDateTime()" x-cloak>
                <div class="col-md-4 mb-3">
                    <label for="scheduled_date" class="form-label">
                        Data <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror"
                           id="scheduled_date" name="scheduled_date"
                           value="{{ old('scheduled_date') }}"
                           :required="showDateTime()">
                    @error('scheduled_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="scheduled_start_time" class="form-label">Ora</label>
                    <input type="time" class="form-control @error('scheduled_start_time') is-invalid @enderror"
                           id="scheduled_start_time" name="scheduled_start_time"
                           value="{{ old('scheduled_start_time') }}">
                    @error('scheduled_start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-3" x-show="tipo === 'pianificazione'" x-cloak>
                    <label for="estimated_duration_minutes" class="form-label">Durata Stimata (minuti)</label>
                    <input type="number" class="form-control @error('estimated_duration_minutes') is-invalid @enderror"
                           id="estimated_duration_minutes" name="estimated_duration_minutes"
                           value="{{ old('estimated_duration_minutes') }}" min="1">
                    @error('estimated_duration_minutes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- ===== CAMPI COMUNI ===== --}}
            <div class="mb-3">
                <label for="title" class="form-label">Titolo Ticket <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror"
                       id="title" name="title" value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if(in_array($userRole, ['operator', 'manutentore']))
                <input type="hidden" name="status" value="open">
            @else
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Stato</label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status">
                        <option value="open" {{ old('status', 'open') == 'open' ? 'selected' : '' }}>Aperto</option>
                        <option value="planned" {{ old('status') == 'planned' ? 'selected' : '' }}>Pianificato</option>
                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In corso</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Annullato</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-check-circle me-2"></i>Salva Ticket
                </button>
                <a href="{{ route('interventions.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Annulla
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
