@props([
    'areas',
    'departments',
    'equipments',
])

<div x-data="standaloneReportModal({{ $departments->toJson() }}, {{ $equipments->toJson() }})"
     x-cloak
     @open-standalone-report.window="open()"
     @keydown.escape.window="close()">

    <div x-show="isOpen"
         x-transition.opacity
         @click.self="close()"
         class="fixed inset-0 z-[58] bg-black/60 flex items-end">

        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl max-h-[94vh] flex flex-col">

            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
                <div class="flex-1 min-w-0">
                    <div class="text-base font-semibold text-gray-900">Nuovo rapportino libero</div>
                    <div class="text-xs text-gray-500">Attività non legata a un ticket</div>
                </div>
                <button type="button" @click="close()"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                        aria-label="Chiudi">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit()" class="flex-1 overflow-y-auto">
                <div class="p-4 space-y-4">

                    {{-- Data --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                            Data <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="form.report_date" :max="todayStr" required
                               @input="errors.report_date = null"
                               x-ref="field_report_date"
                               class="w-full h-11 px-3 border rounded-xl bg-white text-base"
                               :class="errors.report_date ? 'border-red-500 bg-red-50' : 'border-gray-300'">
                        <template x-if="errors.report_date">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.report_date"></div>
                        </template>
                    </div>

                    {{-- Tempo impiegato (select 00:30 → 12:00, step 30 min) --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                            Tempo impiegato <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.duration" required
                                @change="errors.duration = null"
                                x-ref="field_duration"
                                class="w-full h-11 px-3 border rounded-xl bg-white text-base"
                                :class="errors.duration ? 'border-red-500 bg-red-50' : 'border-gray-300'">
                            <option value="">— Seleziona —</option>
                            <template x-for="opt in durationPresets" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-if="errors.duration">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.duration"></div>
                        </template>
                    </div>

                    {{-- Attività --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                            Attività svolte <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="form.activities" x-ref="field_activities"
                                  @input="autoGrow($refs.field_activities); errors.activities = null"
                                  rows="3" required
                                  class="w-full px-3 py-2 border rounded-xl bg-white text-base resize-none"
                                  :class="errors.activities ? 'border-red-500 bg-red-50' : 'border-gray-300'"
                                  placeholder="Cosa hai fatto"></textarea>
                        <template x-if="errors.activities">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.activities"></div>
                        </template>
                    </div>

                    {{-- Area (facoltativa) --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Area</label>
                        <select x-model="form.area_id" @change="onAreaChange()"
                                class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                            <option value="">—</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Zona (facoltativa) --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Zona</label>
                        <select x-model="form.department_id" @change="onDeptChange()"
                                class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                            <option value="">—</option>
                            <template x-for="d in filteredDepts" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Impianto (facoltativo, ricerca) --}}
                    <div class="relative" @click.away="equipmentMenuOpen = false">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Impianto</label>
                        <div class="relative">
                            <input type="text" x-model="equipmentQuery" @focus="equipmentMenuOpen = true"
                                   @input="equipmentMenuOpen = true"
                                   placeholder="Cerca impianto per nome o codice…"
                                   class="w-full h-11 pl-3 pr-10 border border-gray-300 rounded-xl bg-white text-base">
                            <template x-if="form.equipment_id">
                                <button type="button" @click="clearEquipment()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full text-gray-400 active:bg-gray-100"
                                        aria-label="Rimuovi impianto">
                                    <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                        <template x-if="equipmentMenuOpen && filteredEquipments.length > 0">
                            <div class="absolute z-10 mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="e in filteredEquipments" :key="e.id">
                                    <button type="button" @click="pickEquipment(e)"
                                            class="w-full text-left px-3 py-2.5 hover:bg-gray-50 active:bg-gray-100 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 3h4m-4 18h4m-6-9h8M5 7h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/>
                                        </svg>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900 truncate" x-text="e.name"></div>
                                            <div class="text-[11px] text-gray-500 truncate" x-text="e.code || ''"></div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Allegati --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Allegati</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center text-gray-500 active:bg-gray-100 cursor-pointer">
                                <input type="file" class="sr-only" accept="image/*" capture="environment" @change="onFiles($event)">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 9h-6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-2M9 9l2-3h2l2 3M12 14a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
                                </svg>
                                <span class="text-xs mt-1">Foto</span>
                            </label>
                            <label class="h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center text-gray-500 active:bg-gray-100 cursor-pointer">
                                <input type="file" class="sr-only" multiple accept="image/*,application/pdf,.zip" @change="onFiles($event)">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-6 5-5 5 5m-5-5v12"/>
                                </svg>
                                <span class="text-xs mt-1">Carica file</span>
                            </label>
                        </div>

                        <template x-if="files.length > 0">
                            <ul class="mt-3 space-y-1.5">
                                <template x-for="(f, idx) in files" :key="idx">
                                    <li class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-200">
                                        <template x-if="f.preview">
                                            <img :src="f.preview" alt="" class="w-8 h-8 rounded object-cover shrink-0">
                                        </template>
                                        <template x-if="!f.preview">
                                            <span class="w-8 h-8 rounded bg-gray-200 text-gray-500 flex items-center justify-center text-[10px] font-bold shrink-0" x-text="f.ext"></span>
                                        </template>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm text-gray-800 truncate" x-text="f.name"></div>
                                            <div class="text-[11px] text-gray-500" x-text="f.sizeLabel"></div>
                                        </div>
                                        <button type="button" @click="removeFile(idx)" class="w-7 h-7 rounded-full text-gray-500 active:bg-gray-200" aria-label="Rimuovi">
                                            <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                            </svg>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </div>

                <div class="border-t border-gray-200 p-3 bg-white sticky bottom-0 pb-[env(safe-area-inset-bottom)]">
                    <button type="submit" :disabled="submitting"
                            class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold text-base disabled:opacity-50 active:bg-brand-700">
                        <span x-show="!submitting">Salva rapportino</span>
                        <span x-show="submitting">Salvataggio…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function standaloneReportModal(allDepartments, allEquipments) {
            return {
                isOpen: false,
                submitting: false,
                depts: allDepartments,
                equipments: allEquipments,
                equipmentQuery: '',
                equipmentMenuOpen: false,
                todayStr: new Date().toISOString().slice(0, 10),
                form: {
                    report_date: '',
                    duration: '',
                    activities: '',
                    area_id: '',
                    department_id: '',
                    equipment_id: '',
                },
                errors: {},
                files: [],
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

                durationPresets: (() => {
                    const out = [];
                    for (let total = 30; total <= 720; total += 30) {
                        const h = Math.floor(total / 60);
                        const m = total % 60;
                        const value = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        let label;
                        if (h === 0)      label = `${m} min`;
                        else if (m === 0) label = `${h} h`;
                        else              label = `${h} h ${m} min`;
                        out.push({ label, value });
                    }
                    return out;
                })(),

                open() {
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                    this.form = {
                        report_date: this.todayStr,
                        duration: '',
                        activities: '',
                        area_id: '',
                        department_id: '',
                        equipment_id: '',
                    };
                    this.errors = {};
                    this.equipmentQuery = '';
                    this.equipmentMenuOpen = false;
                    this.files = [];
                },

                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    this.files.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
                },

                get filteredDepts() {
                    if (!this.form.area_id) return this.depts;
                    return this.depts.filter((d) => String(d.area_id) === String(this.form.area_id));
                },

                get filteredEquipments() {
                    let list = this.equipments;
                    if (this.form.department_id) {
                        list = list.filter((e) => String(e.department_id) === String(this.form.department_id));
                    } else if (this.form.area_id) {
                        const inArea = this.depts
                            .filter((d) => String(d.area_id) === String(this.form.area_id))
                            .map((d) => String(d.id));
                        list = list.filter((e) => inArea.includes(String(e.department_id)));
                    }
                    if (this.equipmentQuery) {
                        const q = this.equipmentQuery.toLowerCase();
                        list = list.filter((e) =>
                            (e.name || '').toLowerCase().includes(q)
                            || (e.code || '').toLowerCase().includes(q));
                    }
                    return list.slice(0, 50);
                },

                onAreaChange() {
                    if (this.form.department_id) {
                        const d = this.depts.find((x) => String(x.id) === String(this.form.department_id));
                        if (d && String(d.area_id) !== String(this.form.area_id) && this.form.area_id !== '') {
                            this.form.department_id = '';
                        }
                    }
                    if (this.form.area_id) {
                        const inArea = this.depts.filter((d) => String(d.area_id) === String(this.form.area_id));
                        if (inArea.length === 1) this.form.department_id = String(inArea[0].id);
                    }
                    this.clearEquipmentIfIncompatible();
                },

                onDeptChange() {
                    this.clearEquipmentIfIncompatible();
                },

                clearEquipmentIfIncompatible() {
                    if (!this.form.equipment_id) return;
                    const e = this.equipments.find((x) => String(x.id) === String(this.form.equipment_id));
                    if (!e) { this.clearEquipment(); return; }
                    if (this.form.department_id && String(e.department_id) !== String(this.form.department_id)) {
                        this.clearEquipment();
                    } else if (!this.form.department_id && this.form.area_id) {
                        const d = this.depts.find((x) => String(x.id) === String(e.department_id));
                        if (!d || String(d.area_id) !== String(this.form.area_id)) this.clearEquipment();
                    }
                },

                pickEquipment(e) {
                    this.form.equipment_id = String(e.id);
                    this.equipmentQuery = e.name + (e.code ? ' (' + e.code + ')' : '');
                    this.equipmentMenuOpen = false;
                    const d = this.depts.find((x) => String(x.id) === String(e.department_id));
                    if (d) {
                        this.form.area_id = String(d.area_id || '');
                        this.form.department_id = String(d.id);
                    }
                },

                clearEquipment() {
                    this.form.equipment_id = '';
                    this.equipmentQuery = '';
                },

                onFiles(event) {
                    const list = Array.from(event.target.files || []);
                    list.forEach((file) => {
                        const isImage = file.type.startsWith('image/');
                        this.files.push({
                            file,
                            name: file.name,
                            ext: (file.name.split('.').pop() || '').toUpperCase().slice(0, 4),
                            sizeLabel: this.formatSize(file.size),
                            preview: isImage ? URL.createObjectURL(file) : null,
                        });
                    });
                    event.target.value = '';
                },

                removeFile(idx) {
                    const f = this.files[idx];
                    if (f?.preview) URL.revokeObjectURL(f.preview);
                    this.files.splice(idx, 1);
                },

                formatSize(bytes) {
                    const units = ['B', 'KB', 'MB', 'GB'];
                    let i = 0;
                    while (bytes > 1024 && i < units.length - 1) { bytes /= 1024; i++; }
                    return `${bytes.toFixed(bytes >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
                },

                autoGrow(el) {
                    if (!el) return;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 300) + 'px';
                },

                focusFirstError() {
                    const order = ['report_date', 'duration', 'activities'];
                    for (const name of order) {
                        if (this.errors[name]) {
                            const el = this.$refs['field_' + name];
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                el.focus({ preventScroll: true });
                            }
                            return;
                        }
                    }
                },

                validateForm() {
                    const errs = {};
                    if (!this.form.report_date) {
                        errs.report_date = 'Indica la data del rapportino.';
                    }
                    if (!this.form.duration) {
                        errs.duration = 'Indica il tempo impiegato.';
                    } else {
                        const [h, m] = this.form.duration.split(':').map((v) => Number(v) || 0);
                        if ((h * 60 + m) <= 0) {
                            errs.duration = 'Il tempo impiegato deve essere maggiore di 0.';
                        }
                    }
                    if (!this.form.activities || !this.form.activities.trim()) {
                        errs.activities = 'Descrivi le attività svolte.';
                    }
                    this.errors = errs;
                    return Object.keys(errs).length === 0;
                },

                async submit() {
                    if (this.submitting) return;
                    if (!this.validateForm()) {
                        const first = Object.values(this.errors)[0];
                        this.$store.toasts.push(first || 'Compila i campi obbligatori.', 'error');
                        this.$nextTick(() => this.focusFirstError());
                        return;
                    }
                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    fd.append('report_date', this.form.report_date);
                    fd.append('duration', this.form.duration);
                    fd.append('activities', this.form.activities);
                    if (this.form.area_id) fd.append('area_id', this.form.area_id);
                    if (this.form.department_id) fd.append('department_id', this.form.department_id);
                    if (this.form.equipment_id) fd.append('equipment_id', this.form.equipment_id);
                    this.files.forEach((f) => fd.append('files[]', f.file));

                    try {
                        const res = await fetch(@json(route('m.reports.store-standalone')), {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        });
                        if (res.status === 422) {
                            const payload = await res.json();
                            if (payload.errors) {
                                this.errors = Object.fromEntries(
                                    Object.entries(payload.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
                                );
                                this.$nextTick(() => this.focusFirstError());
                            }
                            const first = payload.errors ? Object.values(payload.errors).flat()[0] : payload.message;
                            this.$store.toasts.push(first || 'Dati non validi.', 'error');
                            return;
                        }
                        const payload = await res.json();
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Rapportino salvato.', 'success');
                            this.close();
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore durante il salvataggio.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>
    @endpush
@endonce
