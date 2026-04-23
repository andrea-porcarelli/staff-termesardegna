{{-- Modal dettagli ticket. Si apre via evento `open-ticket` con { id }. --}}

<div x-data="ticketModal()"
     x-cloak
     @open-ticket.window="open($event.detail.id)"
     @ticket-refresh.window="if (isOpen && ticket) refresh()"
     @close-ticket.window="if (isOpen) close()"
     @keydown.escape.window="handleEscape()">

    {{-- ─── MAIN SHEET ──────────────────────────────────────────────── --}}
    <div x-show="isOpen"
         x-transition.opacity
         @click.self="close()"
         class="fixed inset-0 z-50 bg-black/60 flex items-end">

        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl max-h-[94vh] flex flex-col">

            {{-- Header sticky --}}
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
                <div x-show="loading" class="text-sm text-gray-500">Caricamento…</div>

                <template x-if="!loading && ticket">
                    <div class="flex items-center gap-2 flex-1 min-w-0 flex-wrap">
                        <span class="font-mono font-semibold text-gray-700 text-sm" x-text="ticket.code"></span>
                        <template x-if="ticket.reschedule">
                            <span class="inline-flex items-center gap-1 h-5 px-1.5 rounded bg-amber-100 text-amber-800 text-[10px] font-semibold"
                                  :title="'Rinviato da ' + (ticket.reschedule.user_name || '—')">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0 1 14.7-3.7L23 9M20 15a9 9 0 0 1-14.7 3.7L1 15"/>
                                </svg>
                                <span x-text="'rinviato al ' + ticket.reschedule.date"></span>
                            </span>
                        </template>
                        <span class="text-gray-300">·</span>
                        <span class="text-sm text-gray-500 truncate" x-text="ticket.created_at"></span>
                    </div>
                </template>

                <button type="button" @click="close()"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                        aria-label="Chiudi">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            {{-- Body scrollable --}}
            <div class="flex-1 overflow-y-auto" x-show="!loading && ticket">

                {{-- Richiesta di collaborazione in arrivo --}}
                <template x-if="ticket?.pending_request">
                    <div class="mx-4 mt-4 p-3 rounded-xl bg-amber-50 border border-amber-300">
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6m-3-3h6"/>
                            </svg>
                            <div class="flex-1 text-sm">
                                <div class="font-semibold text-amber-900">
                                    Richiesta di collaborazione da <span x-text="ticket.pending_request.requested_by"></span>
                                </div>
                                <div class="text-amber-800 mt-0.5" x-show="ticket.pending_request.message" x-text="ticket.pending_request.message"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button @click="respondCollab('accept')"
                                    class="h-10 rounded-lg bg-emerald-600 text-white font-semibold text-sm active:bg-emerald-700">
                                Accetta
                            </button>
                            <button @click="respondCollab('decline')"
                                    class="h-10 rounded-lg bg-white border border-gray-300 text-gray-700 font-semibold text-sm active:bg-gray-50">
                                Rifiuta
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Banner scaduto --}}
                <template x-if="ticket && ticket.is_overdue">
                    <div class="mx-4 mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        <div class="text-sm">
                            <div class="font-semibold">Scaduto da <span x-text="ticket.overdue_since"></span></div>
                            <div class="text-xs text-red-600/80" x-text="'era il ' + ticket.deadline_at"></div>
                        </div>
                    </div>
                </template>

                {{-- Titolo + chip --}}
                <div class="px-4 pt-4 space-y-3">
                    <h2 class="text-xl font-bold text-gray-900" x-text="ticket?.title || 'Ticket'"></h2>

                    <div class="flex flex-wrap gap-1.5">
                        <template x-if="ticket?.area">
                            <span class="inline-flex h-7 px-2.5 items-center rounded-md bg-gray-100 text-gray-700 text-xs font-medium"
                                  x-text="(ticket?.area || '') + (ticket?.department ? ' / ' + ticket.department : '')"></span>
                        </template>
                        <template x-if="ticket?.equipment">
                            <span class="inline-flex h-7 px-2.5 items-center rounded-md bg-gray-100 text-gray-700 text-xs font-medium" x-text="ticket.equipment"></span>
                        </template>
                        <template x-if="ticket?.maintenance_role">
                            <span class="inline-flex h-7 px-2.5 items-center rounded-md bg-gray-100 text-gray-700 text-xs font-medium" x-text="ticket.maintenance_role"></span>
                        </template>
                        <template x-if="ticket?.tipo === 'pianificazione'">
                            <span class="inline-flex h-7 px-2.5 items-center gap-1 rounded-md bg-red-50 text-red-600 border border-red-200 text-xs font-medium">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 4a1 1 0 0 0-1 1v3H9V5a1 1 0 1 0-2 0v4a1 1 0 0 0 1 1h3v8a1 1 0 1 0 2 0v-8h3a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1z"/>
                                </svg>
                                pianificato
                            </span>
                        </template>
                        <template x-if="ticket?.tipo !== 'pianificazione'">
                            <span class="inline-flex h-7 px-2.5 items-center rounded-md bg-gray-100 text-gray-600 border border-gray-200 text-xs font-medium">libero</span>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="inline-flex h-7 px-2.5 items-center rounded-md border text-xs font-semibold"
                              :class="statusClass(ticket?.status)"
                              x-text="ticket?.status_label"></span>
                        <span class="inline-flex h-7 px-2.5 items-center rounded-md text-xs font-semibold"
                              :class="priorityClass(ticket?.priority)"
                              x-text="ticket?.priority_label"></span>
                    </div>

                    <div class="pt-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Assegnatario</div>
                        <template x-if="ticket?.unassigned">
                            <div class="inline-flex items-center gap-2 px-2.5 h-9 rounded-full bg-gray-100 text-gray-500">
                                <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12"/>
                                    </svg>
                                </span>
                                <span class="text-sm italic">Non assegnato</span>
                            </div>
                        </template>
                        <template x-if="!ticket?.unassigned && ticket?.assigned_user">
                            <div class="inline-flex items-center gap-2 pl-1 pr-3 h-9 rounded-full border"
                                 :class="ticket?.mine ? 'border-brand-300 bg-brand-50' : 'border-gray-200 bg-white'">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold"
                                      :class="ticket?.mine ? 'bg-brand-100 text-brand-800' : 'bg-sky-100 text-sky-800'"
                                      x-text="ticket.assigned_user.initials"></span>
                                <span class="text-sm font-semibold text-gray-900" x-text="ticket.assigned_user.name"></span>
                                <template x-if="ticket?.mine">
                                    <span class="text-[11px] font-semibold text-brand-700 uppercase tracking-wide">Tu</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Collaboratori --}}
                <template x-if="ticket?.collaborators?.length">
                    <div class="px-4 pt-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Collaboratori</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in ticket.collaborators" :key="c.id">
                                <div class="inline-flex items-center gap-1.5 h-7 pl-1 pr-2 rounded-full border"
                                     :class="c.status === 'accepted' ? 'border-emerald-300 bg-emerald-50' : 'border-amber-300 bg-amber-50'">
                                    <span class="w-5 h-5 rounded-full bg-white flex items-center justify-center text-[9px] font-bold text-gray-700" x-text="c.initials"></span>
                                    <span class="text-xs font-medium text-gray-800" x-text="c.name"></span>
                                    <span class="text-[10px]"
                                          :class="c.status === 'accepted' ? 'text-emerald-700' : 'text-amber-700'"
                                          x-text="c.status === 'accepted' ? '✓' : '…'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Dettagli --}}
                <div class="px-4 py-4 space-y-4">
                    <template x-if="ticket?.description">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Descrizione</div>
                            <p class="text-sm text-gray-800 whitespace-pre-line" x-text="ticket.description"></p>
                        </div>
                    </template>

                    <template x-if="ticket?.notes">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Note</div>
                            <p class="text-sm text-gray-800 whitespace-pre-line" x-text="ticket.notes"></p>
                        </div>
                    </template>
                </div>

                {{-- Rapportini del ticket (conteggio per utente + flag finale) --}}
                <template x-if="ticket?.report_statuses?.length">
                    <div class="px-4 pb-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                            Rapportini del ticket
                        </div>
                        <ul class="space-y-1.5">
                            <template x-for="rs in ticket.report_statuses" :key="rs.user_id">
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center text-[10px] font-bold" x-text="rs.initials"></span>
                                    <span class="text-gray-800" x-text="rs.name"></span>
                                    <span class="text-[11px] text-gray-400" x-text="'· ' + rs.role"></span>
                                    <span class="ml-auto inline-flex items-center gap-1">
                                        <span class="inline-flex h-5 px-2 items-center rounded bg-gray-100 text-gray-600 text-[10px] font-semibold"
                                              x-text="(rs.count ?? 0) + ' rapportin' + ((rs.count ?? 0) === 1 ? 'o' : 'i')"></span>
                                        <template x-if="rs.has_final">
                                            <span class="inline-flex h-5 px-2 items-center rounded bg-emerald-100 text-emerald-700 text-[10px] font-semibold">✓ finale</span>
                                        </template>
                                        <template x-if="!rs.has_final && rs.next_work_date">
                                            <span class="inline-flex h-5 px-2 items-center rounded bg-amber-100 text-amber-800 text-[10px] font-semibold" x-text="'↻ ' + rs.next_work_date"></span>
                                        </template>
                                    </span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- Storico --}}
                <template x-if="ticket?.history?.length">
                    <div class="px-4 pb-6">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Storico</div>
                        <ol class="space-y-3">
                            <template x-for="ev in ticket.history" :key="ev.event + '-' + ev.id">
                                <li class="flex gap-3">
                                    <span class="mt-1 w-2 h-2 rounded-full shrink-0"
                                          :class="historyColor(ev.event)"></span>
                                    <div class="flex-1 text-sm">
                                        <div class="text-gray-900" x-html="historyLabel(ev)"></div>
                                        <div class="text-xs text-gray-500 mt-0.5" x-text="ev.at"></div>
                                        <div class="text-xs text-gray-600 italic mt-1" x-show="ev.reason" x-text="'“' + (ev.reason || '') + '”'"></div>
                                        <div class="text-xs text-gray-600 italic mt-1" x-show="ev.message" x-text="'“' + (ev.message || '') + '”'"></div>
                                    </div>
                                </li>
                            </template>
                        </ol>
                    </div>
                </template>
            </div>

            {{-- Azioni sticky in fondo --}}
            <div class="border-t border-gray-200 p-3 space-y-2 bg-white pb-[env(safe-area-inset-bottom)]" x-show="!loading && ticket">

                <template x-if="ticket?.actions?.can_take_charge">
                    <button type="button"
                            @click="takeCharge()"
                            :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 w-full h-12 px-5 text-base font-semibold rounded-xl bg-brand-600 text-white active:bg-brand-700 disabled:opacity-50">
                        <span x-show="!submitting">Prendi in carico</span>
                        <span x-show="submitting">Attendere…</span>
                    </button>
                </template>

                <template x-if="ticket?.actions?.can_create_report">
                    <button type="button"
                            @click="openReport()"
                            class="inline-flex items-center justify-center gap-2 w-full h-12 px-5 text-base font-semibold rounded-xl bg-brand-600 text-white active:bg-brand-700">
                        Crea rapportino
                    </button>
                </template>

                <template x-if="ticket?.i_have_final">
                    <div class="inline-flex items-center justify-center gap-2 w-full h-11 px-5 text-sm font-semibold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
                        </svg>
                        Hai terminato il tuo lavoro su questo ticket
                    </div>
                </template>

                <template x-if="!ticket?.i_have_final && ticket?.my_next_work_date">
                    <div class="inline-flex items-center justify-center gap-2 w-full h-11 px-5 text-sm font-semibold rounded-xl bg-amber-50 text-amber-800 border border-amber-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                        </svg>
                        <span x-text="'Tornerai sul ticket il ' + ticket.my_next_work_date"></span>
                    </div>
                </template>

                <template x-if="ticket?.actions?.can_transfer || ticket?.actions?.can_collaborate">
                    <div class="grid grid-cols-2 gap-2">
                        <template x-if="ticket?.actions?.can_transfer">
                            <button type="button"
                                    @click="openSub('transfer')"
                                    class="h-11 rounded-xl bg-gray-100 text-gray-800 font-semibold text-sm active:bg-gray-200 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0-3-3m3 3-3 3M16 17H4m0 0 3 3m-3-3 3-3"/>
                                </svg>
                                Trasferisci
                            </button>
                        </template>
                        <template x-if="ticket?.actions?.can_collaborate">
                            <button type="button"
                                    @click="openSub('collab')"
                                    class="h-11 rounded-xl bg-gray-100 text-gray-800 font-semibold text-sm active:bg-gray-200 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm14 9v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                Collabora
                            </button>
                        </template>
                    </div>
                </template>

            </div>
        </div>
    </div>

    {{-- ─── SUB-SHEET TRASFERIMENTO / COLLABORAZIONE ───────────────── --}}
    <div x-show="subSheet"
         x-transition.opacity
         @click.self="closeSub()"
         class="fixed inset-0 z-[60] bg-black/60 flex items-end">

        <div x-show="subSheet"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl max-h-[88vh] flex flex-col">

            <div class="flex items-center px-4 py-3 border-b border-gray-200">
                <h3 class="flex-1 text-base font-semibold text-gray-900"
                    x-text="subSheet === 'transfer' ? 'Trasferisci ticket' : 'Richiedi collaborazione'"></h3>
                <button type="button" @click="closeSub()"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                        aria-label="Chiudi">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <div x-show="candidatesLoading" class="text-sm text-gray-500">Carico candidati…</div>

                <template x-if="!candidatesLoading && candidates.length === 0">
                    <div class="py-8 text-center">
                        <div class="text-3xl mb-2">🤷</div>
                        <div class="text-sm text-gray-600">Nessun candidato compatibile per questo ticket.</div>
                    </div>
                </template>

                <template x-if="!candidatesLoading && candidates.length > 0">
                    <div class="space-y-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <span x-text="subSheet === 'transfer' ? 'Nuovo assegnatario' : 'A chi chiedere collaborazione'"></span>
                        </div>
                        <template x-for="c in candidates" :key="c.id">
                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer"
                                   :class="selectedCandidateId === c.id ? 'border-brand-500 bg-brand-50' : 'border-gray-200 active:bg-gray-50'">
                                <input type="radio" :value="c.id" x-model.number="selectedCandidateId" class="sr-only">
                                <span class="w-9 h-9 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center text-xs font-bold"
                                      x-text="c.initials"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate" x-text="c.name"></div>
                                    <div class="text-xs text-gray-500" x-text="c.role"></div>
                                </div>
                                <span x-show="selectedCandidateId === c.id" class="text-brand-600">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
                                    </svg>
                                </span>
                            </label>
                        </template>
                    </div>
                </template>

                <div x-show="!candidatesLoading && candidates.length > 0">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1 mt-3"
                           x-text="subSheet === 'transfer' ? 'Motivo (opzionale)' : 'Messaggio (opzionale)'"></label>
                    <textarea x-model="subMessage" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-base"
                              :placeholder="subSheet === 'transfer' ? 'Es. passo ad altra squadra per…' : 'Es. serve il tuo supporto sul quadro elettrico'"></textarea>
                </div>
            </div>

            <div class="border-t border-gray-200 p-3 pb-[env(safe-area-inset-bottom)]">
                <button type="button"
                        :disabled="!selectedCandidateId || submitting"
                        @click="submitSub()"
                        class="w-full h-12 rounded-xl bg-brand-600 text-white font-semibold text-base disabled:opacity-50 active:bg-brand-700">
                    <span x-show="!submitting" x-text="subSheet === 'transfer' ? 'Conferma trasferimento' : 'Invia richiesta'"></span>
                    <span x-show="submitting">Invio…</span>
                </button>
            </div>
        </div>
    </div>

</div>

@once
    @push('scripts')
    <script>
        function ticketModal() {
            return {
                isOpen: false,
                loading: false,
                ticket: null,
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

                subSheet: null,
                candidates: [],
                candidatesLoading: false,
                selectedCandidateId: null,
                subMessage: '',
                submitting: false,

                async open(id) {
                    this.isOpen = true;
                    this.loading = true;
                    this.ticket = null;
                    document.body.style.overflow = 'hidden';
                    try {
                        const res = await fetch(`/m/tickets/${id}/json`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        this.ticket = await res.json();
                    } catch (e) {
                        this.$store.toasts.push('Impossibile caricare il ticket.', 'error');
                        this.close();
                    } finally {
                        this.loading = false;
                    }
                },

                async refresh() {
                    if (!this.ticket) return;
                    try {
                        const res = await fetch(`/m/tickets/${this.ticket.id}/json`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (res.ok) this.ticket = await res.json();
                    } catch (_) {}
                },

                close() {
                    this.isOpen = false;
                    this.ticket = null;
                    document.body.style.overflow = '';
                },

                handleEscape() {
                    if (this.subSheet) { this.closeSub(); }
                    else { this.close(); }
                },

                openReport() {
                    if (!this.ticket) return;
                    this.$dispatch('open-report', {
                        id:    this.ticket.id,
                        code:  this.ticket.code,
                        title: this.ticket.title,
                        existing_next_work_date: this.ticket.my_next_work_date || null,
                    });
                },

                async takeCharge() {
                    if (!this.ticket || this.submitting) return;
                    this.submitting = true;
                    const body = new FormData();
                    body.append('_token', this.csrf);
                    try {
                        const res = await fetch(this.ticket.urls.take_charge, {
                            method: 'POST',
                            body,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        });
                        const payload = await res.json().catch(() => ({}));
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Ticket preso in carico.', 'success');
                            this.close();
                            setTimeout(() => window.location.reload(), 250);
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore durante la presa in carico.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async openSub(kind) {
                    this.subSheet = kind;
                    this.selectedCandidateId = null;
                    this.subMessage = '';
                    this.candidates = [];
                    this.candidatesLoading = true;
                    try {
                        const url = this.ticket.urls.candidates + '?purpose=' + (kind === 'transfer' ? 'transfer' : 'collaboration');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const payload = await res.json();
                        this.candidates = payload.candidates || [];
                    } catch (_) {
                        this.$store.toasts.push('Impossibile caricare i candidati.', 'error');
                    } finally {
                        this.candidatesLoading = false;
                    }
                },

                closeSub() {
                    this.subSheet = null;
                },

                async submitSub() {
                    if (!this.selectedCandidateId || this.submitting) return;
                    this.submitting = true;

                    const isTransfer = this.subSheet === 'transfer';
                    const url = isTransfer ? this.ticket.urls.transfer : this.ticket.urls.collaboration;
                    const body = new FormData();
                    body.append('_token', this.csrf);
                    if (isTransfer) {
                        body.append('to_user_id', this.selectedCandidateId);
                        if (this.subMessage) body.append('reason', this.subMessage);
                    } else {
                        body.append('user_id', this.selectedCandidateId);
                        if (this.subMessage) body.append('message', this.subMessage);
                    }

                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            body,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        });
                        const payload = await res.json();
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message || 'Operazione completata.', 'success');
                            this.closeSub();
                            await this.refresh();
                            window.dispatchEvent(new CustomEvent('notifications-refresh'));
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore durante l\'operazione.', 'error');
                        }
                    } catch (e) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async respondCollab(decision) {
                    if (!this.ticket?.pending_request) return;
                    const url = this.ticket.pending_request.respond_url;
                    const body = new FormData();
                    body.append('_token', this.csrf);
                    body.append('decision', decision);
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            body,
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        });
                        const payload = await res.json();
                        if (res.ok && payload.ok) {
                            this.$store.toasts.push(payload.message, 'success');
                            await this.refresh();
                            window.dispatchEvent(new CustomEvent('notifications-refresh'));
                        } else {
                            this.$store.toasts.push(payload.message || 'Errore.', 'error');
                        }
                    } catch (_) {
                        this.$store.toasts.push('Errore di rete.', 'error');
                    }
                },

                statusClass(status) {
                    return {
                        'open':        'border-sky-400 text-sky-700 bg-sky-50',
                        'planned':     'border-indigo-400 text-indigo-700 bg-indigo-50',
                        'in_progress': 'border-rose-400 text-rose-700 bg-rose-50',
                        'completed':   'border-emerald-400 text-emerald-700 bg-emerald-50',
                        'cancelled':   'border-gray-300 text-gray-600 bg-gray-50',
                    }[status] || 'border-gray-300 text-gray-600 bg-gray-50';
                },

                priorityClass(priority) {
                    return {
                        'high':       'bg-orange-500 text-white',
                        'low':        'bg-sky-100 text-sky-700',
                        'fixed_date': 'bg-violet-100 text-violet-700',
                    }[priority] || 'bg-gray-100 text-gray-700';
                },

                historyColor(event) {
                    if (event === 'transfer')                 return 'bg-brand-500';
                    if (event === 'collaboration_accepted')   return 'bg-emerald-500';
                    if (event === 'collaboration_declined')   return 'bg-red-500';
                    if (event === 'collaboration_revoked')    return 'bg-gray-500';
                    if (event === 'report_completed')         return 'bg-emerald-600';
                    if (event === 'report_draft')             return 'bg-amber-400';
                    return 'bg-amber-500'; // pending
                },

                historyLabel(ev) {
                    if (ev.event === 'transfer') {
                        return `<span class="font-medium">Trasferito</span> da ${ev.from_user_name || '—'} a <span class="font-medium">${ev.to_user_name}</span>`;
                    }
                    if (ev.event === 'collaboration_accepted') {
                        return `<span class="font-medium">${ev.user_name}</span> ha <span class="text-emerald-700">accettato</span> la collaborazione (richiesta da ${ev.requested_by})`;
                    }
                    if (ev.event === 'collaboration_declined') {
                        return `<span class="font-medium">${ev.user_name}</span> ha <span class="text-red-700">rifiutato</span> la collaborazione`;
                    }
                    if (ev.event === 'collaboration_revoked') {
                        return `Richiesta di collaborazione a <span class="font-medium">${ev.user_name}</span> revocata`;
                    }
                    if (ev.event === 'report_completed') {
                        const dur = ev.duration ? ` <span class="text-gray-500">(${ev.duration})</span>` : '';
                        return `<span class="font-medium">${ev.user_name}</span> ha scritto il <span class="text-emerald-700">rapportino di chiusura</span>${dur}`;
                    }
                    if (ev.event === 'report_draft') {
                        const dur = ev.duration ? ` <span class="text-gray-500">(${ev.duration})</span>` : '';
                        return `<span class="font-medium">${ev.user_name}</span> ha salvato una <span class="text-amber-700">bozza di rapportino</span>${dur}`;
                    }
                    return `Richiesta di collaborazione a <span class="font-medium">${ev.user_name}</span> (in attesa)`;
                },
            };
        }
    </script>
    @endpush
@endonce
