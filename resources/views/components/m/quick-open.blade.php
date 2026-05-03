@props([
    'areas',
    'departments',
    'equipments',
    'maintenanceRoles',
    'manutentori' => collect(),
])

@php
    $creatorRole = auth()->user()->role;
    $isManutentoreCreator = $creatorRole === 'manutentore';
@endphp

<div x-data="quickOpenForm({{ $departments->toJson() }}, {{ $equipments->toJson() }})" x-cloak>
    <div x-show="$store.quickOpen.isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="$store.quickOpen.hide()"
         @keydown.escape.window="$store.quickOpen.hide()"
         class="fixed inset-0 z-40 bg-black/50 flex items-end">

        <form method="POST"
              action="{{ route('m.interventions.quick-open') }}"
              enctype="multipart/form-data"
              x-show="$store.quickOpen.isOpen"
              x-transition:enter="transition ease-out duration-250"
              x-transition:enter-start="translate-y-full"
              x-transition:enter-end="translate-y-0"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="translate-y-0"
              x-transition:leave-end="translate-y-full"
              class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl max-h-[92vh] flex flex-col pb-[env(safe-area-inset-bottom)]">
            @csrf

            <div class="flex items-center justify-between px-4 pt-4 pb-2 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Nuovo ticket</h3>
                <button type="button"
                        @click="$store.quickOpen.hide()"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                        aria-label="Chiudi">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="px-4 py-4 space-y-3 overflow-y-auto">

                {{-- Titolo: obbligatorio per operatore, facoltativo (fallback) per manutentore creatore --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Titolo
                        @unless ($isManutentoreCreator) <span class="text-red-500">*</span> @endunless
                    </label>
                    <input type="text" name="title" maxlength="255"
                           placeholder="{{ $isManutentoreCreator ? 'Titolo breve (facoltativo)' : 'Titolo breve' }}"
                           @unless ($isManutentoreCreator) required @endunless
                           class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                </div>

                @if ($isManutentoreCreator)
                    {{-- Manutentore: il manutentore creatore assegna direttamente a un collega --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                            Manutentore <span class="text-red-500">*</span>
                        </label>
                        <select name="assigned_user_id" required
                                class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                            <option value="">Seleziona manutentore</option>
                            @foreach ($manutentori as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    {{-- Specializzazione: l'operatore sceglie la specializzazione → auto-assegnazione --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                            Specializzazione <span class="text-red-500">*</span>
                        </label>
                        <select name="maintenance_role_id" x-model="maintenanceRoleId" required
                                class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                            <option value="">Seleziona specializzazione</option>
                            @foreach ($maintenanceRoles as $mr)
                                <option value="{{ $mr->id }}">{{ $mr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Priorità (obbligatoria) --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Priorità <span class="text-red-500">*</span>
                    </label>
                    <select name="priority" x-model="priority" required
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">Seleziona priorità</option>
                        <option value="high">Alta</option>
                        <option value="low">Bassa</option>
                        <option value="fixed_date">Data fissa</option>
                    </select>
                </div>

                {{-- Data + ora (solo se fixed_date) --}}
                <template x-if="priority === 'fixed_date'">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Data <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="scheduled_date" x-model="scheduledDate" :min="todayStr"
                                   :required="priority === 'fixed_date'"
                                   class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Ora</label>
                            <input type="time" name="scheduled_start_time" x-model="scheduledTime"
                                   class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        </div>
                    </div>
                </template>

                {{-- Area: obbligatoria per operatore, facoltativa per manutentore creatore --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Area
                        @unless ($isManutentoreCreator) <span class="text-red-500">*</span> @endunless
                    </label>
                    <select name="area_id" x-model="areaId" @change="onAreaChange()"
                            @unless ($isManutentoreCreator) required @endunless
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">{{ $isManutentoreCreator ? 'Tutte le mie aree' : 'Seleziona area' }}</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Zona: obbligatoria per operatore, facoltativa per manutentore creatore --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Zona
                        @unless ($isManutentoreCreator) <span class="text-red-500">*</span> @endunless
                    </label>
                    <select name="department_id" x-model="deptId" @change="onDeptChange()"
                            @unless ($isManutentoreCreator) required @endunless
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        @if ($isManutentoreCreator)
                            <option value="" x-text="areaId ? 'Tutte le zone dell\'area' : 'Tutte le mie zone'"></option>
                        @else
                            <option value="">Seleziona zona</option>
                        @endif
                        <template x-for="d in filteredDepts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Impianto (facoltativo, ricerca) --}}
                <div class="relative" @click.away="equipmentMenuOpen = false">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Impianto</label>
                    <input type="hidden" name="equipment_id" :value="equipmentId">
                    <div class="relative">
                        <input type="text" x-model="equipmentQuery" @focus="equipmentMenuOpen = true"
                               @input="equipmentMenuOpen = true"
                               placeholder="Cerca impianto per nome o codice…"
                               class="w-full h-11 pl-3 pr-10 border border-gray-300 rounded-xl bg-white text-base">
                        <template x-if="equipmentId">
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

                    <template x-if="equipmentMenuOpen && equipmentQuery && filteredEquipments.length === 0">
                        <div class="absolute z-10 mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg px-3 py-3 text-sm text-gray-500">
                            Nessun impianto trovato nelle tue aree/zone.
                        </div>
                    </template>
                </div>

                {{-- Descrizione (facoltativa) --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Descrizione (opzionale)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base"
                              placeholder="Cosa devi fare..."></textarea>
                </div>

                {{-- Allegati (foto / PDF) — opzionali --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Allegati (foto, PDF)</label>
                    <input type="file" name="files[]" multiple
                           accept="image/*,application/pdf,.zip"
                           @change="onFilesChange($event)"
                           class="w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700">
                    <template x-if="filesSummary">
                        <div class="text-[11px] text-gray-500 mt-1" x-text="filesSummary"></div>
                    </template>
                    <div class="text-[11px] text-gray-400 mt-1">Max 10 file, fino a 10&nbsp;MB ciascuno.</div>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-gray-100">
                <x-m.btn type="submit" variant="primary" size="lg" :block="true">
                    Apri ticket
                </x-m.btn>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function quickOpenForm(allDepartments, allEquipments) {
            return {
                maintenanceRoleId: '',
                priority: '',
                scheduledDate: '',
                scheduledTime: '',
                areaId: '',
                deptId: '',
                equipmentId: '',
                equipmentQuery: '',
                equipmentMenuOpen: false,
                depts: allDepartments,
                equipments: allEquipments,
                todayStr: new Date().toISOString().slice(0, 10),
                filesSummary: '',

                init() {
                    this.autoSelectZone();
                },

                onFilesChange(event) {
                    const files = Array.from(event.target.files || []);
                    if (files.length === 0) { this.filesSummary = ''; return; }
                    const totalKb = files.reduce((s, f) => s + f.size, 0) / 1024;
                    const sizeStr = totalKb < 1024
                        ? totalKb.toFixed(0) + ' KB'
                        : (totalKb / 1024).toFixed(1) + ' MB';
                    this.filesSummary = files.length + ' file selezionat' + (files.length === 1 ? 'o' : 'i') + ' (' + sizeStr + ')';
                },

                get filteredDepts() {
                    if (!this.areaId) return this.depts;
                    return this.depts.filter(d => String(d.area_id) === String(this.areaId));
                },

                get filteredEquipments() {
                    let list = this.equipments;
                    if (this.deptId) {
                        list = list.filter(e => String(e.department_id) === String(this.deptId));
                    } else if (this.areaId) {
                        const inArea = this.depts
                            .filter(d => String(d.area_id) === String(this.areaId))
                            .map(d => String(d.id));
                        list = list.filter(e => inArea.includes(String(e.department_id)));
                    }
                    if (this.equipmentQuery) {
                        const q = this.equipmentQuery.toLowerCase();
                        list = list.filter(e =>
                            (e.name || '').toLowerCase().includes(q)
                            || (e.code || '').toLowerCase().includes(q));
                    }
                    return list.slice(0, 50);
                },

                onAreaChange() {
                    // Se la zona selezionata non appartiene più all'area, resettala
                    if (this.deptId) {
                        const d = this.depts.find(x => String(x.id) === String(this.deptId));
                        if (d && String(d.area_id) !== String(this.areaId) && this.areaId !== '') {
                            this.deptId = '';
                        }
                    }
                    this.autoSelectZone();
                    this.clearEquipmentIfIncompatible();
                },

                onDeptChange() {
                    this.clearEquipmentIfIncompatible();
                },

                autoSelectZone() {
                    // Se l'utente ha una sola zona in quell'area, preselezionala.
                    if (!this.areaId) return;
                    const inArea = this.depts.filter(d => String(d.area_id) === String(this.areaId));
                    if (inArea.length === 1) {
                        this.deptId = String(inArea[0].id);
                    }
                },

                clearEquipmentIfIncompatible() {
                    if (!this.equipmentId) return;
                    const e = this.equipments.find(x => String(x.id) === String(this.equipmentId));
                    if (!e) { this.clearEquipment(); return; }
                    if (this.deptId && String(e.department_id) !== String(this.deptId)) {
                        this.clearEquipment();
                    } else if (!this.deptId && this.areaId) {
                        const d = this.depts.find(x => String(x.id) === String(e.department_id));
                        if (!d || String(d.area_id) !== String(this.areaId)) {
                            this.clearEquipment();
                        }
                    }
                },

                pickEquipment(e) {
                    this.equipmentId = String(e.id);
                    this.equipmentQuery = e.name + (e.code ? ' (' + e.code + ')' : '');
                    this.equipmentMenuOpen = false;
                    // Se l'impianto appartiene a un'area/zona diverse, allinea i filtri.
                    const d = this.depts.find(x => String(x.id) === String(e.department_id));
                    if (d) {
                        this.areaId = String(d.area_id || '');
                        this.deptId = String(d.id);
                    }
                },

                clearEquipment() {
                    this.equipmentId = '';
                    this.equipmentQuery = '';
                },
            };
        }
    </script>
    @endpush
@endonce
