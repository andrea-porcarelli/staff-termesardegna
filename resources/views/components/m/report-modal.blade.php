{{-- Modal inline per creare il rapportino. Si apre via evento `open-report` con { id, code, title }. --}}

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

            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
                <div class="flex-1 min-w-0">
                    <div class="text-base font-semibold text-gray-900">Nuovo rapportino</div>
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

            <form @submit.prevent="submit()" class="flex-1 overflow-y-auto">
                <div class="p-4 space-y-4">

                    {{-- Data --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Data</label>
                        <input type="date" x-model="form.report_date" required
                               class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                    </div>

                    {{-- Orari con quick-steps --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Inizio</label>
                            <input type="time" step="300" x-model="form.start_time" required
                                   class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Fine</label>
                            <input type="time" step="300" x-model="form.end_time" required
                                   class="w-full h-11 px-3 border border-gray-300 rounded-xl bg-white text-base">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 -mt-2">
                        <button type="button" @click="bumpEnd(15)"  class="h-8 px-3 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 active:bg-gray-200">+15m</button>
                        <button type="button" @click="bumpEnd(30)"  class="h-8 px-3 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 active:bg-gray-200">+30m</button>
                        <button type="button" @click="bumpEnd(60)"  class="h-8 px-3 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 active:bg-gray-200">+1h</button>
                        <button type="button" @click="nowStart()"   class="h-8 px-3 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 active:bg-sky-200">Inizio: adesso</button>
                    </div>

                    {{-- Attività --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Attività svolte</label>
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
                                <input type="file" class="sr-only"
                                       accept="image/*" capture="environment"
                                       @change="onFiles($event)">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 9h-6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-2M9 9l2-3h2l2 3M12 14a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
                                </svg>
                                <span class="text-xs mt-1">Foto</span>
                            </label>
                            <label class="h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center text-gray-500 active:bg-gray-100 cursor-pointer">
                                <input type="file" class="sr-only" multiple
                                       accept="image/*,application/pdf,.zip"
                                       @change="onFiles($event)">
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
                                        <button type="button" @click="removeFile(idx)"
                                                class="w-7 h-7 rounded-full text-gray-500 active:bg-gray-200"
                                                aria-label="Rimuovi">
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
                    <button type="submit"
                            :disabled="submitting"
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
        function reportModal() {
            return {
                isOpen: false,
                submitting: false,
                context: { id: null, code: '', title: '' },
                form: {
                    report_date: '',
                    start_time: '',
                    end_time: '',
                    activities: '',
                    notes: '',
                    status: 'draft',
                },
                files: [],
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

                open(detail) {
                    this.context = {
                        id:    detail.id,
                        code:  detail.code  || '#' + detail.id,
                        title: detail.title || '',
                    };
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';

                    const now = new Date();
                    const hh = String(now.getHours()).padStart(2, '0');
                    const mm = String(Math.floor(now.getMinutes() / 5) * 5).padStart(2, '0');

                    this.form = {
                        report_date: now.toISOString().slice(0, 10),
                        start_time: `${hh}:${mm}`,
                        end_time: '',
                        activities: '',
                        notes: '',
                        status: 'draft',
                    };
                    this.files = [];
                },

                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    // Cleanup object URLs
                    this.files.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
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
                    // Reset input so re-selecting same file triggers change
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

                bumpEnd(minutes) {
                    const start = this.form.start_time;
                    if (!start) return;
                    const [h, m] = start.split(':').map(Number);
                    const base = new Date(0, 0, 0, h, m);
                    // Se c'è già un end, aggiungo al end. Altrimenti parto da start.
                    if (this.form.end_time) {
                        const [eh, em] = this.form.end_time.split(':').map(Number);
                        const e = new Date(0, 0, 0, eh, em);
                        e.setMinutes(e.getMinutes() + minutes);
                        this.form.end_time = this.fmtTime(e);
                    } else {
                        base.setMinutes(base.getMinutes() + minutes);
                        this.form.end_time = this.fmtTime(base);
                    }
                },

                nowStart() {
                    const now = new Date();
                    const mm = Math.floor(now.getMinutes() / 5) * 5;
                    now.setMinutes(mm, 0, 0);
                    this.form.start_time = this.fmtTime(now);
                },

                fmtTime(d) {
                    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                },

                autoGrow(el) {
                    if (!el) return;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 300) + 'px';
                },

                async submit() {
                    if (this.submitting || !this.context.id) return;
                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    fd.append('report_date', this.form.report_date);
                    fd.append('start_time',  this.form.start_time);
                    fd.append('end_time',    this.form.end_time);
                    fd.append('activities',  this.form.activities);
                    fd.append('notes',       this.form.notes || '');
                    fd.append('status',      this.form.status);
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
                            const first = payload.errors
                                ? Object.values(payload.errors).flat()[0]
                                : payload.message;
                            this.$store.toasts.push(first || 'Dati non validi.', 'error');
                            return;
                        }

                        const payload = await res.json();
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Rapportino salvato.', 'success');
                            this.close();
                            window.dispatchEvent(new CustomEvent('ticket-refresh', { detail: { id: this.context.id } }));
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
