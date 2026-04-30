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
@endphp

@section('content')
    <div class="pt-3 pb-6 px-3"
         x-data="ticketEditForm(
            {{ $quickDepartments->toJson() }},
            {{ $quickEquipments->toJson() }},
            @json([
                'priority' => $iv->priority,
                'scheduledDate' => optional($iv->scheduled_date)->format('Y-m-d'),
                'scheduledTime' => $iv->scheduled_start_time ? substr($iv->scheduled_start_time, 0, 5) : '',
                'areaId' => (string) ($iv->area_id ?? ''),
                'deptId' => (string) ($iv->department_id ?? ''),
                'equipmentId' => (string) ($iv->equipment_id ?? ''),
                'equipmentLabel' => $equipmentLabel,
            ])
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

            <div class="pt-2 grid grid-cols-2 gap-2">
                <a href="{{ route('m.tickets.index') }}"
                   class="h-12 rounded-xl bg-gray-100 text-gray-800 font-semibold text-base flex items-center justify-center active:bg-gray-200">
                    Annulla
                </a>
                <button type="submit"
                        class="h-12 rounded-xl bg-brand-600 text-white font-semibold text-base active:bg-brand-700">
                    Salva
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

    @push('scripts')
    <script>
        function ticketEditForm(allDepartments, allEquipments, initial) {
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
    </script>
    @endpush
@endsection
