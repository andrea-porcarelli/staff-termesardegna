<div x-data="notificationSheet()"
     x-cloak
     x-show="$store.notifications.isOpen"
     @click.self="$store.notifications.hide()"
     @keydown.escape.window="$store.notifications.hide()"
     @notifications-refresh.window="$store.notifications.fetch()"
     x-transition.opacity
     class="fixed inset-0 z-[55] bg-black/60 flex items-end">

    <div x-show="$store.notifications.isOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="bg-white w-full mx-auto max-w-[480px] rounded-t-2xl max-h-[88vh] flex flex-col">

        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900 flex-1">Notifiche</h3>

            <button type="button"
                    @click="$store.notifications.markAllRead()"
                    x-show="$store.notifications.unreadCount > 0"
                    class="h-8 px-2.5 rounded-lg text-xs font-semibold text-brand-700 active:bg-brand-50">
                Segna tutte lette
            </button>

            <button type="button"
                    @click="$store.notifications.hide()"
                    class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 active:bg-gray-100"
                    aria-label="Chiudi">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div x-show="$store.notifications.loading" class="p-4 text-sm text-gray-500">Carico…</div>

            <template x-if="!$store.notifications.loading && $store.notifications.items.length === 0">
                <div class="py-12 text-center">
                    <div class="text-3xl mb-2">🔔</div>
                    <div class="font-medium text-gray-700">Nessuna notifica</div>
                </div>
            </template>

            <ul class="divide-y divide-gray-100" x-show="$store.notifications.items.length > 0">
                <template x-for="n in $store.notifications.items" :key="n.id">
                    <li>
                        <button type="button"
                                @click="onTap(n)"
                                class="w-full text-left flex items-start gap-3 px-4 py-3 active:bg-gray-50"
                                :class="!n.read_at ? 'bg-brand-50/60' : ''">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                                  :class="iconBg(n.type)">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" x-html="iconSvg(n.type)"></svg>
                            </span>

                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-900"
                                     :class="!n.read_at ? 'font-semibold' : 'font-medium'"
                                     x-text="n.headline"></div>
                                <div class="text-xs text-gray-500 mt-0.5" x-show="n.subline" x-text="n.subline"></div>
                                <div class="text-[11px] text-gray-400 mt-1" x-text="n.created_at"></div>
                            </div>

                            <span x-show="!n.read_at" class="w-2 h-2 rounded-full bg-brand-500 shrink-0 mt-2"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function notificationSheet() {
            return {
                async onTap(n) {
                    await this.$store.notifications.markRead(n.id);
                    if (n.intervention_id) {
                        this.$store.notifications.hide();
                        this.$dispatch('open-ticket', { id: n.intervention_id });
                    }
                },
                iconBg(type) {
                    return {
                        transfer_received:       'bg-indigo-100 text-indigo-700',
                        collaboration_requested: 'bg-amber-100 text-amber-700',
                        collaboration_accepted:  'bg-emerald-100 text-emerald-700',
                        collaboration_declined:  'bg-red-100 text-red-700',
                    }[type] || 'bg-gray-100 text-gray-700';
                },
                iconSvg(type) {
                    return {
                        transfer_received:       '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0-3-3m3 3-3 3M16 17H4m0 0 3 3m-3-3 3-3"/>',
                        collaboration_requested: '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6m-3-3h6"/>',
                        collaboration_accepted:  '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>',
                        collaboration_declined:  '<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>',
                    }[type] || '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14V11a6 6 0 1 0-12 0v3a2 2 0 0 1-.6 1.6L4 17h5m6 0a3 3 0 1 1-6 0"/>';
                },
            };
        }
    </script>
    @endpush
@endonce
