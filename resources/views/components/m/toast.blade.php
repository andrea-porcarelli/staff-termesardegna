@php
    $flashes = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error',   'message' => session('error')],
        ['type' => 'error',   'message' => optional(session('errors'))?->first()],
    ])->filter(fn ($f) => !empty($f['message']))->values();
@endphp

<div x-data x-cloak
     class="fixed inset-x-0 top-2 z-50 flex flex-col items-center gap-2 px-3 pointer-events-none">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div @click="$store.toasts.dismiss(t.id)"
             class="pointer-events-auto w-full max-w-[440px] rounded-xl shadow-lg border px-4 py-3 text-sm font-medium flex items-start gap-3"
             :class="{
                'bg-green-600 text-white border-green-700': t.type === 'success',
                'bg-red-600 text-white border-red-700': t.type === 'error',
                'bg-gray-900 text-white border-gray-900': t.type === 'info',
             }">
            <span x-text="t.message" class="flex-1"></span>
            <button class="opacity-80 active:opacity-100" aria-label="Chiudi">×</button>
        </div>
    </template>
</div>

@if ($flashes->isNotEmpty())
    <script>
        (function () {
            const msgs = @json($flashes);
            const enqueue = () => msgs.forEach(m => window.Alpine.store('toasts').push(m.message, m.type));
            if (window.Alpine && window.Alpine.store) {
                enqueue();
            } else {
                document.addEventListener('alpine:initialized', enqueue, { once: true });
            }
        })();
    </script>
@endif
