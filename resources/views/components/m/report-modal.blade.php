{{-- Modal inline per creare il rapportino + flusso chiusura/sospensione. --}}

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
                    <div class="text-base font-semibold text-gray-900"
                         x-text="stepTitle()"></div>
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
                <form @submit.prevent="submit()" class="flex-1 overflow-y-auto">
                    <div class="p-4 space-y-4">

                        {{-- Data/ora redazione (solo lettura) --}}
                        <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            <span>Redatto: <span class="font-semibold text-gray-700" x-text="nowLabel"></span></span>
                        </div>

                        {{-- Tempo impiegato --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Tempo impiegato <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="relative">
                                    <input type="number" inputmode="numeric" min="0" max="24" step="1"
                                           x-model.number="form.hours" required
                                           class="w-full h-11 pl-3 pr-12 border border-gray-300 rounded-xl bg-white text-base"
                                           placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-gray-500">ore</span>
                                </div>
                                <div class="relative">
                                    <input type="number" inputmode="numeric" min="0" max="59" step="1"
                                           x-model.number="form.minutes" required
                                           class="w-full h-11 pl-3 pr-12 border border-gray-300 rounded-xl bg-white text-base"
                                           placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-gray-500">min</span>
                                </div>
                            </div>
                            <div class="text-[11px] text-gray-500 mt-1" x-show="form.hours > 0 || form.minutes > 0">
                                Totale: <span x-text="totalLabel"></span>
                            </div>
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

                        {{-- Stato --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Stato</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center justify-center h-11 rounded-xl border cursor-pointer text-sm font-semibold"
                                       :class="form.status === 'draft' ? 'bg-amber-50 border-amber-400 text-amber-800' : 'bg-white border-gray-300 text-gray-700 active:bg-gray-50'">
                                    <input type="radio" value="draft" x-model="form.status" class="sr-only">
                                    Bozza
                                </label>
                                <label class="flex items-center justify-center h-11 rounded-xl border cursor-pointer text-sm font-semibold"
                                       :class="form.status === 'completed' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'bg-white border-gray-300 text-gray-700 active:bg-gray-50'">
                                    <input type="radio" value="completed" x-model="form.status" class="sr-only">
                                    Completato
                                </label>
                            </div>
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
            </template>

            {{-- ─── STEP 2: VUOI CHIUDERE IL TICKET? ────────────────── --}}
            <template x-if="step === 'close_q'">
                <div class="flex-1 overflow-y-auto p-5 pb-[env(safe-area-inset-bottom)]">
                    <div class="py-2 text-center">
                        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Vuoi chiudere il ticket?</h3>
                        <p class="text-sm text-gray-600 mt-1">Una volta chiuso non sarà più riaperto.</p>

                        <template x-if="result && !result.can_close_ticket">
                            <div class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                                Per chiudere il ticket serve che <span class="font-semibold">tutti i collaboratori</span> abbiano scritto il loro rapportino di chiusura.
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 space-y-2">
                        <button type="button"
                                @click="confirmClose()"
                                :disabled="submitting || !(result?.can_close_ticket)"
                                class="w-full h-12 rounded-xl bg-emerald-600 text-white font-semibold disabled:opacity-50 active:bg-emerald-700">
                            Sì, chiudi il ticket
                        </button>
                        <button type="button" @click="step = 'suspend_q'"
                                class="w-full h-12 rounded-xl bg-white border border-gray-300 text-gray-800 font-semibold active:bg-gray-50">
                            No, non ancora
                        </button>
                    </div>
                </div>
            </template>

            {{-- ─── STEP 3: VUOI SOSPENDERLO? ─────────────────────────── --}}
            <template x-if="step === 'suspend_q'">
                <div class="flex-1 overflow-y-auto p-5 pb-[env(safe-area-inset-bottom)]">
                    <div class="py-2 text-center">
                        <div class="w-14 h-14 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="6" y="5" width="4" height="14" rx="1" stroke-linecap="round"/>
                                <rect x="14" y="5" width="4" height="14" rx="1" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Vuoi sospenderlo?</h3>
                        <p class="text-sm text-gray-600 mt-1">Scegli una data di ripresa oppure lascialo in lavorazione (ricompare domani).</p>
                    </div>

                    <div class="mt-6 space-y-2">
                        <button type="button" @click="step = 'suspend_date'"
                                class="w-full h-12 rounded-xl bg-sky-600 text-white font-semibold active:bg-sky-700">
                            Sì, scegli una data
                        </button>
                        <button type="button" @click="confirmDefer()" :disabled="submitting"
                                class="w-full h-12 rounded-xl bg-white border border-gray-300 text-gray-800 font-semibold disabled:opacity-50 active:bg-gray-50">
                            No, lascia in lavorazione
                        </button>
                    </div>
                </div>
            </template>

            {{-- ─── STEP 4: SCEGLI DATA SOSPENSIONE ────────────────── --}}
            <template x-if="step === 'suspend_date'">
                <div class="flex-1 overflow-y-auto p-5 pb-[env(safe-area-inset-bottom)]">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Quando riprendere?</h3>
                    <p class="text-sm text-gray-600 mb-4">Il ticket tornerà in elenco a partire dalla data scelta.</p>

                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Data di ripresa</label>
                    <input type="date" x-model="suspendUntil" :min="tomorrowStr"
                           class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">

                    <div class="mt-6 space-y-2">
                        <button type="button" @click="confirmSuspend()"
                                :disabled="submitting || !suspendUntil"
                                class="w-full h-12 rounded-xl bg-sky-600 text-white font-semibold disabled:opacity-50 active:bg-sky-700">
                            Conferma sospensione
                        </button>
                        <button type="button" @click="step = 'suspend_q'"
                                class="w-full h-12 rounded-xl bg-white border border-gray-300 text-gray-800 font-semibold active:bg-gray-50">
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
                tomorrowStr: '',
                suspendUntil: '',
                form: {
                    hours: 0,
                    minutes: 0,
                    activities: '',
                    notes: '',
                    status: 'completed',
                },
                files: [],
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

                stepTitle() {
                    return {
                        form:          'Nuovo rapportino',
                        close_q:       'Chiusura ticket',
                        suspend_q:     'Sospensione ticket',
                        suspend_date:  'Scegli data',
                    }[this.step] || 'Rapportino';
                },

                get totalLabel() {
                    const h = Number(this.form.hours) || 0;
                    const m = Number(this.form.minutes) || 0;
                    const parts = [];
                    if (h > 0) parts.push(h + 'h');
                    if (m > 0) parts.push(m + 'm');
                    return parts.length ? parts.join(' ') : '0m';
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
                    this.form = { hours: 0, minutes: 0, activities: '', notes: '', status: 'completed' };
                    this.files = [];

                    const now = new Date();
                    this.nowLabel = now.toLocaleString('it-IT', {
                        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                    });

                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    this.tomorrowStr = tomorrow.toISOString().slice(0, 10);
                    this.suspendUntil = this.tomorrowStr;
                },

                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    this.files.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
                    // Se era stato salvato un rapportino, notifica il ticket-modal di ricaricare
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

                async submit() {
                    if (this.submitting || !this.context.id) return;
                    const total = (Number(this.form.hours) || 0) * 60 + (Number(this.form.minutes) || 0);
                    if (total <= 0) {
                        this.$store.toasts.push('Indica un tempo impiegato maggiore di 0.', 'error');
                        return;
                    }

                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    fd.append('hours',   String(this.form.hours ?? 0));
                    fd.append('minutes', String(this.form.minutes ?? 0));
                    fd.append('activities', this.form.activities);
                    fd.append('notes', this.form.notes || '');
                    fd.append('status', this.form.status);
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
                            // Solo se il rapportino è "completed" (di chiusura) apriamo il flusso close/suspend.
                            if (payload.should_prompt_close) {
                                this.step = 'close_q';
                            } else {
                                this.close();
                            }
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore durante il salvataggio.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async postJSON(url, body = {}) {
                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    Object.entries(body).forEach(([k, v]) => fd.append(k, v));
                    const res = await fetch(url, {
                        method: 'POST', body: fd, credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    });
                    return [res, await res.json().catch(() => ({}))];
                },

                async confirmClose() {
                    if (this.submitting) return;
                    this.submitting = true;
                    try {
                        const url = `/m/interventions/${this.context.id}/close`;
                        const [res, payload] = await this.postJSON(url);
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Ticket chiuso.', 'success');
                            this.close();
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async confirmDefer() {
                    if (this.submitting) return;
                    this.submitting = true;
                    try {
                        const url = `/m/interventions/${this.context.id}/defer`;
                        const [res, payload] = await this.postJSON(url);
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Ticket rinviato a domani.', 'success');
                            this.close();
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async confirmSuspend() {
                    if (this.submitting || !this.suspendUntil) return;
                    this.submitting = true;
                    try {
                        const url = `/m/interventions/${this.context.id}/suspend`;
                        const [res, payload] = await this.postJSON(url, { until: this.suspendUntil });
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Ticket sospeso.', 'success');
                            this.close();
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore.', 'error');
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
