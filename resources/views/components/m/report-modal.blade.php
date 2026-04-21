{{-- Modal inline per creare il rapportino (ticket-bound). --}}

<div x-data="reportModal()"
     x-cloak
     @open-report.window="open($event.detail)"
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

            {{-- Header --}}
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
                <div class="flex-1 min-w-0">
                    <div class="text-base font-semibold text-gray-900" x-text="stepTitle()"></div>
                    <div class="text-xs text-gray-500 truncate" x-show="context.title">
                        <span x-text="context.code"></span>
                        <span>·</span>
                        <span x-text="context.title"></span>
                    </div>
                </div>
                <button type="button" @click="close()"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                        aria-label="Chiudi">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            {{-- ─── STEP 1: FORM RAPPORTINO ─────────────────────────── --}}
            <template x-if="step === 'form'">
                <form @submit.prevent="onFormSubmit()" class="flex-1 overflow-y-auto">
                    <div class="p-4 space-y-4">

                        {{-- Data/ora redazione (solo lettura) --}}
                        <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            <span>Redatto: <span class="font-semibold text-gray-700" x-text="nowLabel"></span></span>
                        </div>

                        {{-- Tempo impiegato (input time unico) --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Tempo impiegato <span class="text-red-500">*</span>
                            </label>
                            <input type="time" x-model="form.duration" required
                                   class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        </div>

                        {{-- Attività --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Attività svolte <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="form.activities" x-ref="activities"
                                      @input="autoGrow($refs.activities)"
                                      rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base resize-none"
                                      placeholder="Cosa hai fatto sul ticket"></textarea>
                        </div>

                        {{-- Note --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Note (opzionale)</label>
                            <textarea x-model="form.notes" x-ref="notes"
                                      @input="autoGrow($refs.notes)"
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base resize-none"
                                      placeholder="Annotazioni, parti sostituite, segnalazioni…"></textarea>
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

                        {{-- Hai finito il lavoro? --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                                Hai terminato il tuo lavoro sul ticket? <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="h-11 rounded-xl border-2 flex items-center justify-center gap-2 cursor-pointer font-semibold text-sm transition-colors"
                                       :class="form.is_final === true ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-300 bg-white text-gray-700 active:bg-gray-50'">
                                    <input type="radio" :value="true" x-model="form.is_final" @change="onYes()" class="sr-only">
                                    Sì, ho finito
                                </label>
                                <label class="h-11 rounded-xl border-2 flex items-center justify-center gap-2 cursor-pointer font-semibold text-sm transition-colors"
                                       :class="form.is_final === false ? 'border-amber-500 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-700 active:bg-gray-50'">
                                    <input type="radio" :value="false" x-model="form.is_final" class="sr-only">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 p-3 bg-white sticky bottom-0 pb-[env(safe-area-inset-bottom)]">
                        <button type="submit" :disabled="submitting"
                                class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold text-base disabled:opacity-50 active:bg-brand-700">
                            <span x-show="!submitting && form.is_final !== false">Salva rapportino</span>
                            <span x-show="!submitting && form.is_final === false">Avanti</span>
                            <span x-show="submitting">Salvataggio…</span>
                        </button>
                    </div>
                </form>
            </template>

            {{-- ─── STEP 2: QUANDO TORNI? ──────────────────────────── --}}
            <template x-if="step === 'when_q'">
                <div class="flex-1 overflow-y-auto p-5 pb-[env(safe-area-inset-bottom)]">
                    <div class="py-2 text-center">
                        <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3M16 3v3M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Quando torni sul ticket?</h3>
                        <p class="text-sm text-gray-600 mt-1">Il ticket tornerà nella tua lista a partire da questa data.</p>
                    </div>

                    <div class="mt-6 space-y-2">
                        <button type="button" @click="chooseTomorrow()"
                                :disabled="submitting"
                                class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold disabled:opacity-50 active:bg-brand-700">
                            A domani
                        </button>
                        <button type="button" @click="step = 'when_date'"
                                :disabled="submitting"
                                class="w-full h-12 rounded-xl bg-white border border-gray-300 text-gray-800 font-semibold active:bg-gray-50">
                            Scegli data
                        </button>
                        <button type="button" @click="step = 'form'"
                                :disabled="submitting"
                                class="w-full h-11 text-sm text-gray-500 active:text-gray-700">
                            Indietro
                        </button>
                    </div>
                </div>
            </template>

            {{-- ─── STEP 3: CALENDARIO DATA ────────────────────────── --}}
            <template x-if="step === 'when_date'">
                <div class="flex-1 overflow-y-auto p-5 pb-[env(safe-area-inset-bottom)]">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Quando torni?</h3>
                    <p class="text-sm text-gray-600 mb-4">Il ticket tornerà in elenco a partire dalla data scelta.</p>

                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Data di rientro</label>
                    <input type="date" x-model="form.next_work_date" :min="tomorrowStr"
                           class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">

                    <div class="mt-6 space-y-2">
                        <button type="button" @click="submit()"
                                :disabled="submitting || !form.next_work_date"
                                class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold disabled:opacity-50 active:bg-brand-700">
                            <span x-show="!submitting">Conferma</span>
                            <span x-show="submitting">Salvataggio…</span>
                        </button>
                        <button type="button" @click="step = 'when_q'"
                                :disabled="submitting"
                                class="w-full h-11 text-sm text-gray-500 active:text-gray-700">
                            Indietro
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function reportModal() {
            return {
                isOpen: false,
                submitting: false,
                context: { id: null, code: '', title: '' },
                step: 'form',
                result: null,
                nowLabel: '',
                todayStr: '',
                tomorrowStr: '',
                form: {
                    duration: '',
                    activities: '',
                    notes: '',
                    is_final: null,
                    next_work_date: '',
                },
                files: [],
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

                stepTitle() {
                    return {
                        form:      'Nuovo rapportino',
                        when_q:    'Quando torni?',
                        when_date: 'Scegli data',
                    }[this.step] || 'Rapportino';
                },

                open(detail) {
                    this.context = {
                        id:    detail.id,
                        code:  detail.code  || '#' + detail.id,
                        title: detail.title || '',
                    };
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';

                    this.step = 'form';
                    this.result = null;
                    this.form = {
                        duration: '',
                        activities: '',
                        notes: '',
                        is_final: null,
                        next_work_date: '',
                    };
                    this.files = [];

                    const now = new Date();
                    this.nowLabel = now.toLocaleString('it-IT', {
                        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                    });
                    this.todayStr = now.toISOString().slice(0, 10);
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    this.tomorrowStr = tomorrow.toISOString().slice(0, 10);
                    this.form.next_work_date = this.tomorrowStr;
                },

                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    this.files.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
                    if (this.result) {
                        window.dispatchEvent(new CustomEvent('ticket-refresh', { detail: { id: this.context.id } }));
                    }
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

                validateBase() {
                    if (!this.form.duration) {
                        this.$store.toasts.push('Indica il tempo impiegato.', 'error');
                        return false;
                    }
                    const [h, m] = this.form.duration.split(':').map((v) => Number(v) || 0);
                    if ((h * 60 + m) <= 0) {
                        this.$store.toasts.push('Il tempo impiegato deve essere maggiore di 0.', 'error');
                        return false;
                    }
                    if (!this.form.activities || !this.form.activities.trim()) {
                        this.$store.toasts.push('Descrivi le attività svolte.', 'error');
                        return false;
                    }
                    if (this.form.is_final === null) {
                        this.$store.toasts.push('Indica se hai terminato il lavoro.', 'error');
                        return false;
                    }
                    return true;
                },

                // Click radio "Sì, ho finito" → auto-submit.
                onYes() {
                    // Assicurati che i campi base siano compilati prima di auto-inviare.
                    if (!this.form.duration || !this.form.activities || !this.form.activities.trim()) {
                        // Lascia che l'utente completi prima, verranno validati al submit.
                        return;
                    }
                    this.submit();
                },

                // Submit del form: se "No" → passa allo step di scelta data, altrimenti invia.
                onFormSubmit() {
                    if (!this.validateBase()) return;
                    if (this.form.is_final === false) {
                        this.step = 'when_q';
                        return;
                    }
                    this.submit();
                },

                chooseTomorrow() {
                    this.form.next_work_date = this.tomorrowStr;
                    this.submit();
                },

                async submit() {
                    if (this.submitting || !this.context.id) return;
                    if (!this.validateBase()) return;
                    if (this.form.is_final === false && !this.form.next_work_date) {
                        this.$store.toasts.push('Indica quando tornerai a lavorare sul ticket.', 'error');
                        return;
                    }

                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    fd.append('duration', this.form.duration);
                    fd.append('activities', this.form.activities);
                    fd.append('notes', this.form.notes || '');
                    fd.append('is_final', this.form.is_final ? '1' : '0');
                    if (!this.form.is_final && this.form.next_work_date) {
                        fd.append('next_work_date', this.form.next_work_date);
                    }
                    this.files.forEach((f) => fd.append('files[]', f.file));

                    try {
                        const res = await fetch(`/m/interventions/${this.context.id}/reports`, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        });

                        if (res.status === 422) {
                            const payload = await res.json();
                            const first = payload.errors ? Object.values(payload.errors).flat()[0] : payload.message;
                            this.$store.toasts.push(first || 'Dati non validi.', 'error');
                            return;
                        }

                        const payload = await res.json();
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Rapportino salvato.', 'success');
                            this.result = payload;
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
