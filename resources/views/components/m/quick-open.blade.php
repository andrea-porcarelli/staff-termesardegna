@props([
    'areas',
    'departments',
])

<div x-data="quickOpenForm({{ $departments->toJson() }})" x-cloak>
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
              x-show="$store.quickOpen.isOpen"
              x-transition:enter="transition ease-out duration-250"
              x-transition:enter-start="translate-y-full"
              x-transition:enter-end="translate-y-0"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="translate-y-0"
              x-transition:leave-end="translate-y-full"
              class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl pb-[env(safe-area-inset-bottom)]">
            @csrf

            <div class="flex items-center justify-between px-4 pt-4 pb-2">
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

            <div class="px-4 pb-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Area</label>
                    <select x-model="areaId" @change="deptId = ''" name="area_id" required
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        <option value="">Seleziona area</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Zona</label>
                    <select x-model="deptId" name="department_id" required :disabled="!areaId"
                            class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base disabled:bg-gray-50 disabled:text-gray-400">
                        <option value="">Seleziona zona</option>
                        <template x-for="d in filteredDepts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Descrizione (opzionale)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base"
                              placeholder="Cosa devi fare..."></textarea>
                </div>

                <x-m.btn type="submit" variant="primary" size="lg" :block="true" class="mt-2">
                    Apri ticket
                </x-m.btn>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function quickOpenForm(allDepartments) {
            return {
                areaId: '',
                deptId: '',
                depts: allDepartments,
                get filteredDepts() {
                    if (!this.areaId) return [];
                    return this.depts.filter(d => String(d.area_id) === String(this.areaId));
                },
            };
        }
    </script>
    @endpush
@endonce
