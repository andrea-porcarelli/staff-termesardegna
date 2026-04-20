@php
    $me = auth()->id();
    $mine = $intervention->assigned_user_id === $me;
    $unassigned = is_null($intervention->assigned_user_id);
    $isOpenToTake = $intervention->status === 'open' && $unassigned;

    // Relazioni già eager-loaded dalla query (filtrate su user corrente).
    $iAmCollaborator = $intervention->relationLoaded('collaborations')
        ? $intervention->collaborations->isNotEmpty()
        : false;
    $hasMyReport = $intervention->relationLoaded('reports')
        ? $intervention->reports->isNotEmpty()
        : false;
    $canMakeReport = ($mine || $iAmCollaborator)
        && $intervention->status === 'in_progress'
        && !$hasMyReport;
    $isOverdue = $intervention->is_overdue
        || ($intervention->tipo === 'pianificazione'
            && $intervention->scheduled_date
            && $intervention->scheduled_date->isPast()
            && !in_array($intervention->status, ['completed', 'cancelled']));

    // Barra laterale colorata + bullet
    if ($isOverdue) {
        $barClass = 'bg-red-500';
    } elseif ($isOpenToTake) {
        $barClass = 'bg-amber-500';
    } elseif ($intervention->status === 'in_progress') {
        $barClass = 'bg-emerald-500';
    } elseif ($intervention->tipo === 'pianificazione') {
        $barClass = 'bg-brand-500';
    } else {
        $barClass = 'bg-gray-300';
    }

    $statusChip = [
        'open'        => ['label' => 'Aperto',       'class' => 'border-sky-400 text-sky-700 bg-sky-50'],
        'planned'     => ['label' => 'Pianificato',  'class' => 'border-indigo-400 text-indigo-700 bg-indigo-50'],
        'in_progress' => ['label' => 'In carico',    'class' => 'border-rose-400 text-rose-700 bg-rose-50'],
        'completed'   => ['label' => 'Completato',   'class' => 'border-emerald-400 text-emerald-700 bg-emerald-50'],
        'cancelled'   => ['label' => 'Sospeso',      'class' => 'border-gray-300 text-gray-600 bg-gray-50'],
    ][$intervention->status] ?? ['label' => $intervention->status, 'class' => 'border-gray-300 text-gray-600 bg-gray-50'];

    $assignedName = $intervention->assignedUser?->name;
    $initials = $assignedName
        ? mb_strtoupper(collect(explode(' ', $assignedName))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode(''))
        : null;

    $equipment = $intervention->equipment;
    $area = $intervention->area ?? $equipment?->department?->area;
    $department = $intervention->department ?? $equipment?->department;

    // "Pianificato" chip con pin / "Libero" chip grigio
    $tipoChip = $intervention->tipo === 'pianificazione'
        ? ['label' => 'pianificato', 'class' => 'bg-red-50 text-red-600 border-red-200', 'pin' => true]
        : ['label' => 'libero',      'class' => 'bg-gray-100 text-gray-600 border-gray-200', 'pin' => false];
@endphp

<button type="button"
        @click="$dispatch('open-ticket', { id: {{ $intervention->id }} })"
        class="w-full text-left relative bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden active:bg-gray-50 transition-colors">

    <span class="absolute inset-y-0 left-0 w-1 {{ $barClass }}"></span>

    <div class="px-4 py-3 pl-5 space-y-2">

        {{-- Riga 1: bullet + #ID + data --}}
        <div class="flex items-center gap-2 text-xs">
            <span class="w-1.5 h-1.5 rounded-full {{ $barClass }}"></span>
            <span class="font-mono font-semibold text-gray-600">#{{ $intervention->id }}</span>
            <span class="ml-auto text-gray-500">{{ $intervention->created_at?->isoFormat('D MMM') }}</span>
        </div>

        {{-- Banner scaduto --}}
        @if ($isOverdue)
            @php
                $expiredAt = $intervention->tipo === 'pianificazione'
                    ? ($intervention->scheduled_date ? $intervention->scheduled_date->copy()->setTimeFromTimeString($intervention->scheduled_start_time ?? '08:00:00') : null)
                    : $intervention->deadline;
            @endphp
            @if ($expiredAt)
                <div class="flex items-center gap-1.5 text-[11px] text-red-700 font-semibold">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    Scaduto da {{ $expiredAt->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }} · era il {{ $expiredAt->isoFormat('D MMM · HH:mm') }}
                </div>
            @endif
        @endif

        {{-- Riga 2: titolo --}}
        <h3 class="font-semibold text-gray-900 leading-snug">
            {{ $intervention->title ?? 'Intervento senza titolo' }}
        </h3>

        {{-- Riga 3: chip info --}}
        <div class="flex flex-wrap gap-1.5">
            @if ($area && $department)
                <span class="inline-flex items-center h-6 px-2 rounded-md bg-gray-100 text-gray-700 text-[11px] font-medium">
                    {{ $area->name }} / {{ $department->name }}
                </span>
            @elseif ($area)
                <span class="inline-flex items-center h-6 px-2 rounded-md bg-gray-100 text-gray-700 text-[11px] font-medium">
                    {{ $area->name }}
                </span>
            @endif

            @if ($intervention->maintenanceRole)
                <span class="inline-flex items-center h-6 px-2 rounded-md bg-gray-100 text-gray-700 text-[11px] font-medium">
                    {{ $intervention->maintenanceRole->name }}
                </span>
            @endif

            <span class="inline-flex items-center gap-1 h-6 px-2 rounded-md border text-[11px] font-medium {{ $tipoChip['class'] }}">
                @if ($tipoChip['pin'])
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 4a1 1 0 0 0-1 1v3H9V5a1 1 0 1 0-2 0v4a1 1 0 0 0 1 1h3v8a1 1 0 1 0 2 0v-8h3a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1z"/>
                    </svg>
                @endif
                {{ $tipoChip['label'] }}
            </span>
        </div>

        {{-- Riga 4: stato + assegnatario --}}
        <div class="flex items-center gap-2 pt-0.5">
            <span class="inline-flex items-center h-6 px-2 rounded-md border text-[11px] font-semibold {{ $statusChip['class'] }}">
                {{ $statusChip['label'] }}
            </span>

            <div class="ml-auto flex items-center gap-1.5">
                @if ($assignedName)
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center text-[10px] font-bold">
                        {{ $initials }}
                    </span>
                    <span class="text-xs text-gray-700 font-medium truncate max-w-[120px]">
                        @if ($mine) A te @else {{ \Illuminate\Support\Str::words($assignedName, 1, '.') }} @endif
                    </span>
                @else
                    <span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="w-3 h-3 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12"/>
                        </svg>
                    </span>
                    <span class="text-xs text-gray-500 italic">Non assegnato</span>
                @endif
            </div>
        </div>
    </div>
</button>
