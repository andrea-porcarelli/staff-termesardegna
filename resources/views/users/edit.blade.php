@extends('layouts.app')

@section('title', 'Gestione Utenti - Modifica utente')

@section('page-title', 'Modifica utente')

@section('content')

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    @php $existingSlots = $existingSlots ?? []; @endphp

                    <div class="row g-4">

                        {{-- ===== COLONNA SINISTRA (col-8) ===== --}}
                        <div class="col-lg-8">

                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">
                                                <i class="bi bi-person me-1"></i>Nome Completo
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   id="name"
                                                   name="name"
                                                   value="{{ old('name', $user->name) }}"
                                                   required
                                                   autofocus>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">
                                                <i class="bi bi-envelope me-1"></i>Email
                                            </label>
                                            <input type="email"
                                                   class="form-control @error('email') is-invalid @enderror"
                                                   id="email"
                                                   name="email"
                                                   value="{{ old('email', $user->email) }}"
                                                   required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Lascia i campi password vuoti se non vuoi modificarla
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">
                                                <i class="bi bi-lock me-1"></i>Nuova Password
                                            </label>
                                            <input type="password"
                                                   class="form-control @error('password') is-invalid @enderror"
                                                   id="password"
                                                   name="password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="password_confirmation" class="form-label">
                                                <i class="bi bi-lock-fill me-1"></i>Conferma Nuova Password
                                            </label>
                                            <input type="password"
                                                   class="form-control"
                                                   id="password_confirmation"
                                                   name="password_confirmation">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="role" class="form-label">
                                            <i class="bi bi-shield-check me-1"></i>Ruolo
                                        </label>
                                        <select class="form-select @error('role') is-invalid @enderror"
                                                id="role"
                                                name="role"
                                                required
                                                onchange="toggleRoleSections()">
                                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Operatore</option>
                                            <option value="manutentore" {{ old('role', $user->role) == 'manutentore' ? 'selected' : '' }}>Manutentore</option>
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Sezione Manutentore: Piano Orario -->
                                    <div id="manutentore-section" class="mb-4" style="display: none;">

                                        <!-- Piano Orario -->
                                        <hr class="my-4">
                                        <div x-data="scheduleManager({{ json_encode($existingSlots) }}, '{{ route('users.schedule.update', $user) }}')">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5><i class="bi bi-calendar-week me-2"></i>Piano Orario</h5>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span x-show="saveStatus" x-cloak
                                                          class="small"
                                                          :class="saveError ? 'text-danger' : 'text-success'"
                                                          x-text="saveStatus">
                                                    </span>
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                            @click="showAddForm = !showAddForm">
                                                        <i class="bi bi-plus-circle me-1"></i>Aggiungi Slot
                                                    </button>
                                                    <button type="button" class="btn btn-success btn-sm"
                                                            @click="saveSlots()"
                                                            :disabled="saving">
                                                        <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                        <i x-show="!saving" class="bi bi-floppy me-1"></i>
                                                        Salva Piano
                                                    </button>
                                                </div>
                                            </div>

                                            <template x-if="slots.length === 0">
                                                <p class="text-muted">Nessuno slot configurato. Clicca "Aggiungi Slot" per iniziare.</p>
                                            </template>

                                            <!-- Form aggiunta slot -->
                                            <div x-show="showAddForm" x-cloak class="card border-primary mb-3">
                                                <div class="card-header bg-primary bg-opacity-10">
                                                    <strong>
                                                        <template x-if="editingIndex === null"><span><i class="bi bi-plus-circle me-2"></i>Nuovo Slot Orario</span></template>
                                                        <template x-if="editingIndex !== null"><span><i class="bi bi-pencil me-2"></i>Modifica Slot Orario</span></template>
                                                    </strong>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Tipo</label>
                                                            <select class="form-select form-select-sm" x-model="newSlot.type">
                                                                <option value="lavorativo">Lavorativo</option>
                                                                <option value="ferie">Ferie</option>
                                                                <option value="riposi">Riposo</option>
                                                            </select>
                                                        </div>

                                                        <!-- Data singola per lavorativo -->
                                                        <div class="col-md-3" x-show="newSlot.type === 'lavorativo'">
                                                            <label class="form-label">Data <span class="text-muted small">(riferimento)</span></label>
                                                            <input type="date" class="form-control form-control-sm" x-model="newSlot.date">
                                                        </div>

                                                        <!-- Range date per ferie/riposo -->
                                                        <div class="col-md-3" x-show="newSlot.type !== 'lavorativo'">
                                                            <label class="form-label">Data inizio</label>
                                                            <input type="date" class="form-control form-control-sm" x-model="newSlot.date_from">
                                                        </div>
                                                        <div class="col-md-3" x-show="newSlot.type !== 'lavorativo'">
                                                            <label class="form-label">Data fine</label>
                                                            <input type="date" class="form-control form-control-sm" x-model="newSlot.date_to">
                                                        </div>

                                                        <div class="col-md-3 d-flex align-items-end" x-show="newSlot.type === 'lavorativo'">
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" id="new_slot_recurring" x-model="newSlot.is_recurring">
                                                                <label class="form-check-label" for="new_slot_recurring">
                                                                    <i class="bi bi-arrow-repeat me-1"></i>Ripetizione settimanale
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-2 mt-1" x-show="newSlot.type === 'lavorativo'">
                                                        <div class="col-md-2">
                                                            <label class="form-label">Inizio</label>
                                                            <input type="time" class="form-control form-control-sm" x-model="newSlot.start_time">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Fine</label>
                                                            <input type="time" class="form-control form-control-sm" x-model="newSlot.end_time">
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" id="new_slot_lunch" x-model="newSlot.has_lunch">
                                                                <label class="form-check-label" for="new_slot_lunch">
                                                                    <i class="bi bi-cup-hot me-1"></i>Pausa pranzo
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-2 mt-1" x-show="newSlot.type === 'lavorativo' && newSlot.has_lunch">
                                                        <div class="col-md-2">
                                                            <label class="form-label">Inizio pausa</label>
                                                            <input type="time" class="form-control form-control-sm" x-model="newSlot.lunch_start">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Fine pausa</label>
                                                            <input type="time" class="form-control form-control-sm" x-model="newSlot.lunch_end">
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-warning mt-2 py-1 px-2 small" x-show="addError" x-cloak x-text="addError"></div>

                                                    <div class="mt-3 d-flex gap-2">
                                                        <button type="button" class="btn btn-primary btn-sm" @click="addSlot()">
                                                            <i class="bi bi-check-circle me-1"></i><span x-text="editingIndex !== null ? 'Salva' : 'Aggiungi'"></span>
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm" @click="resetForm(); addError = ''">
                                                            <i class="bi bi-x-circle me-1"></i>Annulla
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Calendario Piano Orario -->
                                            <div class="mt-4" id="schedule-calendar"></div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- ===== COLONNA DESTRA (col-4) ===== --}}
                        <div class="col-lg-4">


                            <div class="card">
                                <div class="card-body">
                                    <!-- Team + Specializzazione + Priorità (solo Manutentore) -->
                                    <div id="manutentore-right-section" style="display: none;">

                                        @php $userTeamIds = old('teams', $user->teams->pluck('id')->toArray()); @endphp
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="bi bi-people-fill me-1"></i>Team
                                            </label>
                                            <div class="card">
                                                <div class="card-body py-2">
                                                    @forelse($teams as $team)
                                                        <div class="form-check">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   name="teams[]"
                                                                   value="{{ $team->id }}"
                                                                   id="team_{{ $team->id }}"
                                                                   {{ in_array($team->id, $userTeamIds) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="team_{{ $team->id }}">
                                                                {{ $team->name }}
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <p class="text-muted mb-0 small">Nessun team disponibile. <a href="{{ route('teams.create') }}">Crea un team</a>.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="bi bi-award me-1"></i>Specializzazioni e Livello di Competenza
                                            </label>
                                            @php
                                                $userRoleLevels = $user->maintenanceRoles->pluck('pivot.level', 'id')->toArray();
                                                $oldRoles = old('maintenance_roles', []);
                                                $oldLevels = old('maintenance_role_levels', []);
                                            @endphp
                                            <div class="card">
                                                <div class="card-body py-2">
                                                    @forelse($maintenanceRoles as $mRole)
                                                        @php
                                                            $isChecked = !empty($oldRoles) ? in_array($mRole->id, $oldRoles) : isset($userRoleLevels[$mRole->id]);
                                                            $currentLevel = !empty($oldLevels) ? ($oldLevels[$mRole->id] ?? 1) : ($userRoleLevels[$mRole->id] ?? 1);
                                                        @endphp
                                                        <div class="d-flex align-items-center gap-3 py-1">
                                                            <div class="form-check mb-0" style="min-width: 180px;">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="maintenance_roles[]"
                                                                       value="{{ $mRole->id }}"
                                                                       id="mrole_{{ $mRole->id }}"
                                                                       {{ $isChecked ? 'checked' : '' }}
                                                                       onchange="document.getElementById('mlevel_{{ $mRole->id }}').disabled = !this.checked">
                                                                <label class="form-check-label" for="mrole_{{ $mRole->id }}">
                                                                    {{ $mRole->name }}
                                                                </label>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-1">
                                                                @for($lvl = 1; $lvl <= 5; $lvl++)
                                                                    <i class="bi bi-star{{ $lvl <= $currentLevel ? '-fill text-warning' : ' text-muted' }}"
                                                                       style="cursor: pointer; font-size: 1.1rem;"
                                                                       onclick="setLevel({{ $mRole->id }}, {{ $lvl }})"></i>
                                                                @endfor
                                                                <select class="form-select form-select-sm ms-2"
                                                                        name="maintenance_role_levels[{{ $mRole->id }}]"
                                                                        id="mlevel_{{ $mRole->id }}"
                                                                        style="width: 60px;"
                                                                        {{ !$isChecked ? 'disabled' : '' }}
                                                                        onchange="updateStars({{ $mRole->id }}, this.value)">
                                                                    @for($lvl = 1; $lvl <= 5; $lvl++)
                                                                        <option value="{{ $lvl }}" {{ $currentLevel == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                                                                    @endfor
                                                                </select>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p class="text-muted mb-0 small">Nessuna specializzazione disponibile.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                            @error('maintenance_roles')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <hr>
                                    </div>

                                    @php
                                        $userAreaIds      = old('areas',       $user->areas->pluck('id')->toArray());
                                        $userDepartmentIds = old('departments', $user->departments->pluck('id')->toArray());
                                    @endphp

                                    <!-- Aree (solo Operator) -->
                                    <div id="operator-areas-section" style="display: none;">
                                        <label class="form-label">
                                            <i class="bi bi-geo-alt-fill me-1"></i>Aree Assegnate
                                        </label>
                                        <div class="card mb-3">
                                            <div class="card-body py-2">
                                                @forelse($areas as $area)
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               name="areas[]"
                                                               value="{{ $area->id }}"
                                                               id="area_{{ $area->id }}"
                                                               {{ in_array($area->id, $userAreaIds) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="area_{{ $area->id }}">
                                                            {{ $area->name }}
                                                        </label>
                                                    </div>
                                                @empty
                                                    <p class="text-muted mb-0 small">Nessuna area configurata</p>
                                                @endforelse
                                            </div>
                                        </div>
                                        <hr>
                                    </div>

                                    <!-- Zone (Operator e Manutentore) -->
                                    <div id="departments-section" style="display: none;">
                                        <label class="form-label">
                                            <i class="bi bi-building me-1"></i>Zone Assegnate
                                        </label>
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                @forelse($areas as $area)
                                                    <div class="mb-3">
                                                        <h6 class="fw-bold text-primary mb-1">
                                                            <i class="bi bi-geo-alt-fill me-1"></i>{{ $area->name }}
                                                        </h6>
                                                        @forelse($area->departments as $department)
                                                            <div class="form-check ms-3">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       name="departments[]"
                                                                       value="{{ $department->id }}"
                                                                       id="dept_{{ $department->id }}"
                                                                       {{ in_array($department->id, $userDepartmentIds) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="dept_{{ $department->id }}">
                                                                    {{ $department->name }}
                                                                </label>
                                                            </div>
                                                        @empty
                                                            <p class="text-muted ms-3 mb-0 small">Nessuna zona disponibile</p>
                                                        @endforelse
                                                    </div>
                                                @empty
                                                    <p class="text-muted mb-0">Nessuna area configurata</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pulsanti -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                                        </button>
                                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-x-circle me-2"></i>Annulla
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>{{-- /row --}}

                </form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
const TYPE_CONFIG = {
    lavorativo:   { label: 'Lavorativo',   badge: 'bg-success',               calColor: '#198754' },
    ferie:        { label: 'Ferie',         badge: 'bg-warning text-dark',     calColor: '#ffc107' },
    riposi:       { label: 'Riposo',        badge: 'bg-secondary',             calColor: '#6c757d' },
    pausa_pranzo: { label: 'Pausa pranzo',  badge: 'bg-orange text-white',     calColor: '#fd7e14' },
};

function scheduleManager(initialSlots, saveUrl) {
    return {
        slots: initialSlots || [],
        showAddForm: false,
        addError: '',
        saving: false,
        saveStatus: '',
        saveError: false,
        calendar: null,
        saveUrl: saveUrl,
        newSlot: {
            date: '', date_from: '', date_to: '', start_time: '', end_time: '', type: 'lavorativo',
            is_recurring: false, has_lunch: false, lunch_start: '', lunch_end: '',
        },
        editingIndex: null,

        init() {
            this.$nextTick(() => this.initCalendar());
        },

        async saveSlots() {
            this.saving = true;
            this.saveStatus = '';
            this.saveError = false;
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ slots: this.slots }),
                });
                const data = await response.json();
                if (response.ok) {
                    this.saveStatus = data.message || 'Salvato.';
                    this.saveError = false;
                } else {
                    this.saveStatus = data.message || 'Errore nel salvataggio.';
                    this.saveError = true;
                }
            } catch (e) {
                this.saveStatus = 'Errore di rete.';
                this.saveError = true;
            }
            this.saving = false;
            setTimeout(() => { this.saveStatus = ''; }, 3000);
        },

        initCalendar() {
            const el = document.getElementById('schedule-calendar');
            if (!el || typeof FullCalendar === 'undefined') return;
            const self = this;
            this.calendar = new FullCalendar.Calendar(el, {
                initialView: 'timeGridWeek',
                locale: 'it',
                firstDay: 1,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay' },
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: true,
                height: 520,
                editable: true,
                selectable: false,
                events: (info, successCallback) => {
                    successCallback(self.getSlotsAsEvents(info.start, info.end));
                },
                eventDrop(info) {
                    const idx = info.event.extendedProps.slotIndex;
                    if (idx == null) { info.revert(); return; }
                    const slot = self.slots[idx];
                    if (!slot) { info.revert(); return; }
                    const isRec = slot.is_recurring === '1' || slot.is_recurring === true;
                    if (isRec) { info.revert(); return; }
                    slot.date = info.event.start.toISOString().slice(0, 10);
                    slot.start_time = info.event.start.toTimeString().slice(0, 5);
                    if (info.event.end) {
                        slot.end_time = info.event.end.toTimeString().slice(0, 5);
                    }
                },
                eventResize(info) {
                    const idx = info.event.extendedProps.slotIndex;
                    if (idx == null) { info.revert(); return; }
                    const slot = self.slots[idx];
                    if (!slot) { info.revert(); return; }
                    if (info.event.end) {
                        slot.end_time = info.event.end.toTimeString().slice(0, 5);
                    }
                    if (info.event.start) {
                        slot.start_time = info.event.start.toTimeString().slice(0, 5);
                    }
                },
                eventClick(info) {
                    const idx = info.event.extendedProps.slotIndex;
                    if (idx == null) return;
                    self.editingIndex = idx;
                    const slot = self.slots[idx];
                    if (!slot) return;
                    self.newSlot = {
                        date: slot.date || '',
                        date_from: slot.date || '',
                        date_to: slot.date || '',
                        start_time: slot.start_time || '',
                        end_time: slot.end_time || '',
                        type: slot.type || 'lavorativo',
                        is_recurring: slot.is_recurring === '1' || slot.is_recurring === true,
                        has_lunch: false,
                        lunch_start: '',
                        lunch_end: '',
                    };
                    self.showAddForm = true;
                },
            });
            this.calendar.render();
            window.__scheduleCalendar = this.calendar;
        },

        getSlotsAsEvents(rangeStart, rangeEnd) {
            // Collect dates with ferie/riposo
            const offDays = new Set();
            this.slots.forEach(slot => {
                if ((slot.type === 'ferie' || slot.type === 'riposi') && slot.date) {
                    offDays.add(slot.date);
                }
            });

            const events = [];
            this.slots.forEach((slot, index) => {
                const cfg = TYPE_CONFIG[slot.type] || TYPE_CONFIG.lavorativo;
                const isRecurring = slot.is_recurring === '1' || slot.is_recurring === true;
                const textColor = slot.type === 'ferie' ? '#212529' : '#fff';
                const isAllDay = (slot.type === 'ferie' || slot.type === 'riposi') && !slot.start_time;
                const isWorkType = slot.type === 'lavorativo' || slot.type === 'pausa_pranzo';

                if (isRecurring) {
                    // Expand into concrete dates within the visible range, skipping off days
                    const dow = parseInt(slot.day_of_week);
                    let d = new Date(rangeStart);
                    while (d <= rangeEnd) {
                        if (d.getDay() === dow) {
                            const dateStr = d.toISOString().slice(0, 10);
                            if (!isWorkType || !offDays.has(dateStr)) {
                                events.push({
                                    title: cfg.label,
                                    start: dateStr + 'T' + slot.start_time,
                                    end:   dateStr + 'T' + slot.end_time,
                                    backgroundColor: cfg.calColor,
                                    borderColor: cfg.calColor,
                                    textColor,
                                    editable: false,
                                    extendedProps: { slotIndex: index },
                                });
                            }
                        }
                        d.setDate(d.getDate() + 1);
                    }
                    return;
                }

                if (isAllDay) {
                    events.push({
                        title: cfg.label,
                        start: slot.date,
                        allDay: true,
                        backgroundColor: cfg.calColor,
                        borderColor: cfg.calColor,
                        textColor,
                        extendedProps: { slotIndex: index },
                    });
                    return;
                }

                // Non-recurring with time: skip work slots on off days
                if (isWorkType && offDays.has(slot.date)) return;

                events.push({
                    title: cfg.label,
                    start: slot.date + 'T' + slot.start_time,
                    end:   slot.date + 'T' + slot.end_time,
                    backgroundColor: cfg.calColor,
                    borderColor: cfg.calColor,
                    textColor,
                    extendedProps: { slotIndex: index },
                });
            });
            return events;
        },

        refreshCalendar() {
            if (!this.calendar) return;
            this.calendar.refetchEvents();
        },

        addSlot() {
            this.addError = '';
            const isRangeType = this.newSlot.type === 'ferie' || this.newSlot.type === 'riposi';

            if (!isRangeType) {
                if (!this.newSlot.start_time || !this.newSlot.end_time) {
                    this.addError = 'Inserisci orario di inizio e fine.'; return;
                }
                if (!this.newSlot.date) {
                    this.addError = 'Inserisci una data di riferimento.'; return;
                }
                if (this.newSlot.has_lunch && (!this.newSlot.lunch_start || !this.newSlot.lunch_end)) {
                    this.addError = 'Inserisci gli orari della pausa pranzo.'; return;
                }
            } else {
                if (!this.newSlot.date_from || !this.newSlot.date_to) {
                    this.addError = 'Inserisci data inizio e data fine.'; return;
                }
                if (this.newSlot.date_from > this.newSlot.date_to) {
                    this.addError = 'La data di inizio deve essere precedente alla data di fine.'; return;
                }
            }

            // If editing an existing slot, remove it first
            if (this.editingIndex !== null) {
                this.slots.splice(this.editingIndex, 1);
                this.editingIndex = null;
            }

            if (isRangeType) {
                // Create one slot per day in the range
                let current = new Date(this.newSlot.date_from + 'T00:00:00');
                const end = new Date(this.newSlot.date_to + 'T00:00:00');
                while (current <= end) {
                    const dateStr = current.toISOString().slice(0, 10);
                    this.slots.push({
                        date: dateStr,
                        day_of_week: '',
                        start_time: '',
                        end_time: '',
                        type: this.newSlot.type,
                        is_recurring: '0',
                    });
                    current.setDate(current.getDate() + 1);
                }
            } else {
                const dayOfWeek = String(new Date(this.newSlot.date + 'T00:00:00').getDay());
                const base = {
                    date:         this.newSlot.is_recurring ? '' : this.newSlot.date,
                    day_of_week:  this.newSlot.is_recurring ? dayOfWeek : '',
                    type:         this.newSlot.type,
                    is_recurring: this.newSlot.is_recurring ? '1' : '0',
                };

                if (this.newSlot.has_lunch && this.newSlot.lunch_start && this.newSlot.lunch_end) {
                    this.slots.push({ ...base, start_time: this.newSlot.start_time,  end_time: this.newSlot.lunch_start });
                    this.slots.push({ ...base, start_time: this.newSlot.lunch_start, end_time: this.newSlot.lunch_end,  type: 'pausa_pranzo' });
                    this.slots.push({ ...base, start_time: this.newSlot.lunch_end,   end_time: this.newSlot.end_time });
                } else {
                    this.slots.push({ ...base, start_time: this.newSlot.start_time, end_time: this.newSlot.end_time });
                }
            }

            this.resetForm();
            this.refreshCalendar();
        },

        resetForm() {
            this.newSlot = { date: '', date_from: '', date_to: '', start_time: '', end_time: '', type: 'lavorativo', is_recurring: false, has_lunch: false, lunch_start: '', lunch_end: '' };
            this.editingIndex = null;
            this.showAddForm = false;
        },

        removeSlot(index) {
            this.slots.splice(index, 1);
            this.refreshCalendar();
        },

        getDayName(dow) {
            const days = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
            const i = parseInt(dow);
            return isNaN(i) ? '' : (days[i] ?? '');
        },

        getTypeBadgeClass(type) {
            return (TYPE_CONFIG[type] || TYPE_CONFIG.lavorativo).badge;
        },

        getTypeLabel(type) {
            return (TYPE_CONFIG[type] || TYPE_CONFIG.lavorativo).label;
        },
    };
}

function setLevel(roleId, level) {
    const select = document.getElementById('mlevel_' + roleId);
    const checkbox = document.getElementById('mrole_' + roleId);
    if (!checkbox.checked) { checkbox.checked = true; select.disabled = false; }
    select.value = level;
    updateStars(roleId, level);
}

function updateStars(roleId, level) {
    const row = document.getElementById('mrole_' + roleId).closest('.d-flex');
    row.querySelectorAll('.bi-star-fill, .bi-star').forEach((star, i) => {
        star.className = (i < level)
            ? 'bi bi-star-fill text-warning'
            : 'bi bi-star text-muted';
        star.style.cursor = 'pointer';
        star.style.fontSize = '1.1rem';
    });
}

function toggleRoleSections() {
    const role = document.getElementById('role').value;
    const manutentoreSection      = document.getElementById('manutentore-section');
    const manutentoreRightSection = document.getElementById('manutentore-right-section');
    const operatorAreasSection    = document.getElementById('operator-areas-section');
    const departmentsSection      = document.getElementById('departments-section');

    manutentoreSection.style.display      = (role === 'manutentore') ? 'block' : 'none';
    manutentoreRightSection.style.display = (role === 'manutentore') ? 'block' : 'none';
    operatorAreasSection.style.display    = (role === 'operator')    ? 'block' : 'none';
    departmentsSection.style.display      = (role === 'operator' || role === 'manutentore') ? 'block' : 'none';

    if (role === 'manutentore' && window.__scheduleCalendar) {
        setTimeout(() => window.__scheduleCalendar.updateSize(), 50);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleRoleSections();
});
</script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
    .bg-orange { background-color: #fd7e14 !important; }
    #schedule-calendar .fc-toolbar-title { font-size: 1rem; }
    #schedule-calendar .fc-event { font-size: 0.75rem; }
</style>
@endpush
@endsection
