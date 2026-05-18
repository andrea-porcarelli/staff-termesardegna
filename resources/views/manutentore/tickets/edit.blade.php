@extends('layouts.manutentore')

@section('title', 'Modifica ticket')

@php
    $creatorRole = auth()->user()->role;
    $isManutentoreCreator = $creatorRole === 'manutentore';
    $iv = $intervention;
    $currentEquipment = $iv->equipment_id ? $quickEquipments->firstWhere('id', $iv->equipment_id) : null;
    $equipmentLabel = $currentEquipment
        ? $currentEquipment->name . ($currentEquipment->code ? ' (' . $currentEquipment->code . ')' : '')
        : '';
    $initial = [
        'priority' => $iv->priority,
        'scheduledDate' => optional($iv->scheduled_date)->format('Y-m-d'),
        'scheduledTime' => $iv->scheduled_start_time ? substr($iv->scheduled_start_time, 0, 5) : '',
        'areaId' => (string) ($iv->area_id ?? ''),
        'deptId' => (string) ($iv->department_id ?? ''),
        'equipmentId' => (string) ($iv->equipment_id ?? ''),
        'equipmentLabel' => $equipmentLabel,
    ];
@endphp

@section('content')
    <div class="pt-3 pb-6 px-3"
         x-data="ticketEditForm(
            {{ $quickDepartments->toJson() }},
            {{ $quickEquipments->toJson() }},
            @json($initial)
         )">

        <div class="flex items-center gap-2 mb-3">
            <a href="{{ route('m.tickets.index') }}"
               class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 active:bg-gray-100"
               aria-label="Indietro">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Modifica ticket #{{ $iv->id }}</h1>
        </div>

        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('m.tickets.update', $iv) }}"
              enctype="multipart/form-data"
              x-ref="mainForm"
              @submit.prevent="onSubmit($event)"
              class="bg-white rounded-2xl border border-gray-200 p-4 space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Titolo</label>
                <input type="text" name="title" maxlength="255" value="{{ old('title', $iv->title) }}"
                       placeholder="Titolo breve (facoltativo)"
                       class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
            </div>

            @if ($isManutentoreCreator)
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Manutentore <span class="text-red-500">*</span>
                    </label>
                    <select name="assigned_user_id" required
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">Seleziona manutentore</option>
                        @foreach ($quickManutentori as $m)
                            <option value="{{ $m->id }}" @selected(old('assigned_user_id', $iv->assigned_user_id) == $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                        Specializzazione <span class="text-red-500">*</span>
                    </label>
                    <select name="maintenance_role_id" required
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">Seleziona specializzazione</option>
                        @foreach ($quickMaintenanceRoles as $mr)
                            <option value="{{ $mr->id }}" @selected(old('maintenance_role_id', $iv->maintenance_role_id) == $mr->id)>{{ $mr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Manutentore (opzionale)</label>
                    <select name="assigned_user_id"
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">— Non modificare —</option>
                        @foreach ($quickManutentori as $m)
                            <option value="{{ $m->id }}" @selected(old('assigned_user_id', $iv->assigned_user_id) == $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <div class="text-[11px] text-gray-400 mt-1">Lascia invariato per mantenere l'assegnazione attuale.</div>
                </div>
            @endif

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

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Area</label>
                <select name="area_id" x-model="areaId" @change="onAreaChange()"
                        class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                    <option value="">Tutte le mie aree</option>
                    @foreach ($quickAreas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Zona</label>
                <select name="department_id" x-model="deptId" @change="onDeptChange()"
                        class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                    <option value="" x-text="areaId ? 'Tutte le zone dell\'area' : 'Tutte le mie zone'"></option>
                    <template x-for="d in filteredDepts" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
            </div>

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
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Descrizione</label>
                <textarea name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base"
                          placeholder="Cosa devi fare...">{{ old('description', $iv->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Allegati</label>

                @php($existingMedia = $iv->media)
                @if ($existingMedia->count() > 0)
                    <ul class="space-y-1.5 mb-3">
                        @foreach ($existingMedia as $m)
                            @php($isImage = str_contains((string) $m->file_type, 'image'))
                            <li class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-200"
                                x-show="!removedMediaIds.includes({{ $m->id }})">
                                @if ($isImage)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($m->file_path) }}"
                                         alt="" class="w-10 h-10 rounded object-cover shrink-0">
                                @else
                                    <span class="w-10 h-10 rounded bg-gray-200 text-gray-500 flex items-center justify-center text-[10px] font-bold shrink-0">
                                        {{ strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                    </span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($m->file_path) }}"
                                       target="_blank" rel="noopener"
                                       class="text-sm text-gray-800 truncate block hover:underline">{{ $m->file_name }}</a>
                                </div>
                                <button type="button" @click="removeExistingMedia({{ $m->id }})"
                                        class="w-7 h-7 rounded-full text-red-500 active:bg-red-50" aria-label="Elimina">
                                    <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                    </svg>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <template x-for="id in removedMediaIds" :key="id">
                    <input type="hidden" name="delete_media_ids[]" :value="id">
                </template>

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

            <div class="pt-2 grid grid-cols-2 gap-2">
                <a href="{{ route('m.tickets.index') }}"
                   class="h-12 rounded-xl bg-gray-100 text-gray-800 font-semibold text-base flex items-center justify-center active:bg-gray-200">
                    Annulla
                </a>
                <button type="submit"
                        :disabled="submitting"
                        class="h-12 rounded-xl bg-brand-600 text-white font-semibold text-base active:bg-brand-700 disabled:opacity-60">
                    <span x-show="!submitting">Salva</span>
                    <span x-show="submitting" x-cloak>Salvataggio…</span>
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('m.tickets.destroy', $iv) }}"
              class="mt-3"
              onsubmit="return confirm('Eliminare definitivamente questo ticket?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="w-full h-11 rounded-xl bg-red-50 text-red-700 font-semibold text-sm border border-red-200 active:bg-red-100 flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/>
                </svg>
                Elimina ticket
            </button>
        </form>
    </div>

    <script>
        console.log('[edit.blade] inline script eseguito — definisco ticketEditForm');
        function ticketEditForm(allDepartments, allEquipments, initial) {
            console.log('[ticketEditForm] CHIAMATA', { allDepartments, allEquipments, initial });
            return {
                priority: initial.priority || '',
                scheduledDate: initial.scheduledDate || '',
                scheduledTime: initial.scheduledTime || '',
                areaId: initial.areaId || '',
                deptId: initial.deptId || '',
                equipmentId: initial.equipmentId || '',
                equipmentQuery: initial.equipmentLabel || '',
                equipmentMenuOpen: false,
                depts: allDepartments,
                equipments: allEquipments,
                todayStr: new Date().toISOString().slice(0, 10),
                files: [],
                removedMediaIds: [],
                submitting: false,

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

                removeExistingMedia(id) {
                    if (!confirm('Eliminare questo allegato?')) return;
                    if (!this.removedMediaIds.includes(id)) {
                        this.removedMediaIds.push(id);
                    }
                },

                async onSubmit(event) {
                    if (this.submitting) return;
                    this.submitting = true;
                    const form = this.$refs.mainForm;
                    const fd = new FormData(form);
                    // I file dell'array Alpine vengono allegati manualmente: in alcuni
                    // browser mobile DataTransfer non popola in modo affidabile un
                    // input[type=file] nascosto, quindi bypassiamo il problema.
                    this.files.forEach((f) => fd.append('files[]', f.file, f.name));
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            headers: { 'Accept': 'text/html,application/xhtml+xml' },
                            credentials: 'same-origin',
                        });
                        // Laravel risponde 302 sul successo: fetch segue il redirect e
                        // imposta res.redirected/res.url alla destinazione finale.
                        if (res.redirected && res.url) {
                            window.location.href = res.url;
                            return;
                        }
                        if (res.ok) {
                            window.location.href = '{{ route('m.tickets.index') }}';
                            return;
                        }
                        // 422 (validation) o altri errori: ricarico la pagina edit per
                        // mostrare i messaggi flash. I file selezionati vanno persi:
                        // limitazione di HTTP, non possiamo evitarlo.
                        window.location.reload();
                    } catch (err) {
                        this.submitting = false;
                        alert('Errore di rete. Riprova.');
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
                    if (this.equipmentQuery && this.equipmentId === '') {
                        const q = this.equipmentQuery.toLowerCase();
                        list = list.filter(e =>
                            (e.name || '').toLowerCase().includes(q)
                            || (e.code || '').toLowerCase().includes(q));
                    }
                    return list.slice(0, 50);
                },

                onAreaChange() {
                    if (this.deptId) {
                        const d = this.depts.find(x => String(x.id) === String(this.deptId));
                        if (d && String(d.area_id) !== String(this.areaId) && this.areaId !== '') {
                            this.deptId = '';
                        }
                    }
                    this.clearEquipmentIfIncompatible();
                },

                onDeptChange() {
                    this.clearEquipmentIfIncompatible();
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
        window.ticketEditForm = ticketEditForm;
        console.log('[edit.blade] window.ticketEditForm =', typeof window.ticketEditForm);
    </script>
@endsection
