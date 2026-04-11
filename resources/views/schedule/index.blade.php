@extends('layouts.app')

@section('title', 'Il Mio Piano Orario')

@section('page-title', 'Il Mio Piano Orario')

@section('content')

<div class="card">
    <div class="card-body">
        <div x-data="scheduleManager({{ json_encode($existingSlots) }}, '{{ route('schedule.update') }}')">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Piano Orario</h5>
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
