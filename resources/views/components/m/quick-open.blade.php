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

<div x-data="quickOpenForm({{ $departments->toJson() }}, {{ $equipments->toJson() }}, {{ Js::from(route('m.interventions.similar-open')) }})" x-cloak>
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
                           x-model="title"
                           @input.debounce.350ms="searchSimilar()"
                           placeholder="{{ $isManutentoreCreator ? 'Titolo breve (facoltativo)' : 'Titolo breve' }}"
                           @unless ($isManutentoreCreator) required @endunless
                           class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">

                    {{-- Preview ticket aperti/in lavorazione simili nelle zone dell'utente. --}}
                    <template x-if="similarLoading">
                        <div class="mt-2 text-[12px] text-gray-400">Ricerca ticket simili…</div>
                    </template>
                    <template x-if="!similarLoading && similar.length > 0">
                        <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50">
                            <div class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800">
                                Ticket simili già aperti
                            </div>
                            <ul class="divide-y divide-amber-100">
                                <template x-for="t in similar" :key="t.id">
                                    <li>
                                        <button type="button" @click="openSimilar(t.id)"
                                                class="w-full text-left px-3 py-2 active:bg-amber-100 flex items-start gap-2">
                                            <span class="text-[11px] font-bold text-amber-700 shrink-0 mt-0.5" x-text="t.code"></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm text-gray-900 truncate" x-text="t.title"></span>
                                                <span class="block text-[11px] text-gray-500 truncate">
                                                    <span x-text="t.status_label"></span>
                                                    <template x-if="t.department">
                                                        <span> · <span x-text="t.department"></span></span>
                                                    </template>
                                                    <template x-if="t.equipment">
                                                        <span> · <span x-text="t.equipment"></span></span>
                                                    </template>
                                                    <template x-if="t.assigned_user">
                                                        <span> · <span x-text="t.assigned_user"></span></span>
                                                    </template>
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <div class="px-3 py-2 text-[11px] text-amber-700">
                                Tocca un ticket per aprirlo, oppure continua a compilare per crearne uno nuovo.
                            </div>
                        </div>
                    </template>
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

                    {{-- Manutentore: opzionale. Se valorizzato bypassa l'auto-assegnazione. --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Manutentore (opzionale)</label>
                        <select name="assigned_user_id"
                                class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                            <option value="">Auto-assegnazione</option>
                            @foreach ($manutentori as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-[11px] text-gray-400 mt-1">Lascia vuoto per far scegliere il sistema.</div>
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
                        <option value="medium">Media</option>
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
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Allegati</label>

                    {{-- Input nascosto realmente inviato col form. Popolato via DataTransfer da onFiles(). --}}
                    <input type="file" name="files[]" multiple x-ref="fileInput" class="hidden">

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

                    <div class="text-[11px] text-gray-400 mt-2">Max 10 file, fino a 10&nbsp;MB ciascuno.</div>
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
        function quickOpenForm(allDepartments, allEquipments, similarUrl) {
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
                files: [],
                title: '',
                similar: [],
                similarLoading: false,
                similarUrl: similarUrl,
                similarReqId: 0,

                init() {
                    this.autoSelectZone();
                },

                async searchSimilar() {
                    const q = (this.title || '').trim();
                    if (q.length < 3) {
                        this.similar = [];
                        this.similarLoading = false;
                        return;
                    }
                    const reqId = ++this.similarReqId;
                    this.similarLoading = true;
                    try {
                        const url = `${this.similarUrl}?q=${encodeURIComponent(q)}`;
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        if (reqId !== this.similarReqId) return; // risposta vecchia, scarta
                        this.similar = Array.isArray(data.items) ? data.items : [];
                    } catch (_) {
                        if (reqId === this.similarReqId) this.similar = [];
                    } finally {
                        if (reqId === this.similarReqId) this.similarLoading = false;
                    }
                },

                openSimilar(id) {
                    // Chiudi il modal di apertura ticket e apri il modal dettagli del ticket esistente.
                    if (window.Alpine && Alpine.store('quickOpen')) Alpine.store('quickOpen').hide();
                    window.dispatchEvent(new CustomEvent('open-ticket', { detail: { id } }));
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
                    this.syncFileInput();
                },

                removeFile(idx) {
                    const f = this.files[idx];
                    if (f?.preview) URL.revokeObjectURL(f.preview);
                    this.files.splice(idx, 1);
                    this.syncFileInput();
                },

                syncFileInput() {
                    const dt = new DataTransfer();
                    this.files.forEach((f) => dt.items.add(f.file));
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.files = dt.files;
                    }
                },

                formatSize(bytes) {
                    const units = ['B', 'KB', 'MB', 'GB'];
                    let i = 0;
                    while (bytes > 1024 && i < units.length - 1) { bytes /= 1024; i++; }
                    return `${bytes.toFixed(bytes >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
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
