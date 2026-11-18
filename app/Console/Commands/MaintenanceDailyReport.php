<?php

namespace App\Console\Commands;

use App\Models\Intervention;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkScheduleSlot;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MaintenanceDailyReport extends Command
{
    protected $signature = 'maintenance:daily-report
        {--date= : Giorno da riepilogare in formato YYYY-MM-DD (default: ieri, Europe/Rome)}
        {--mail= : Indirizzo email a cui inviare il report (altrimenti stampa a video)}';

    protected $description = 'Riepilogo giornaliero delle manutenzioni (ticket, pianificazioni, rapportini) per il giorno precedente.';

    /** Area esclusa dai conteggi perché usata per test. */
    private const EXCLUDED_AREA_NAME = 'TEST – non intervenire';

    /** Prefisso per utenti di test da escludere. */
    private const EXCLUDED_USER_PREFIX = 'TEST';

    /** Finestra di tolleranza oraria: se un manutentore ha registrato almeno 30' in meno delle ore previste, va segnalato. */
    private const TOLERANCE_MINUTES = 30;

    /** Soglie di scadenza per i ticket ordinari, per priorità, in ore. */
    private const ORDINARY_DEADLINE_HOURS = [
        'high' => 24,
        'medium' => 48,
        'low' => 24 * 7,
    ];

    private const DAY_NAMES_IT = [
        0 => 'domenica', 1 => 'lunedì', 2 => 'martedì', 3 => 'mercoledì',
        4 => 'giovedì', 5 => 'venerdì', 6 => 'sabato',
    ];

    private const MONTH_NAMES_IT = [
        1 => 'gennaio', 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile',
        5 => 'maggio', 6 => 'giugno', 7 => 'luglio', 8 => 'agosto',
        9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre',
    ];

    private const TZ = 'Europe/Rome';

    public function handle(): int
    {
        $tz = self::TZ;
        $now = CarbonImmutable::now($tz);

        if ($this->option('date')) {
            try {
                $target = CarbonImmutable::parse($this->option('date'), $tz)->startOfDay();
            } catch (\Throwable $e) {
                $this->error('Data non valida: '.$this->option('date'));

                return self::FAILURE;
            }
        } else {
            $target = $now->subDay()->startOfDay();
        }

        $today = $now->startOfDay();

        $report = $this->buildReport($target, $today);

        if ($this->option('mail')) {
            return $this->sendEmail($this->option('mail'), $target, $report);
        }

        // Scrittura "raw" per non lasciare che Symfony Console interpreti eventuali
        // caratteri '<' nei titoli dei ticket come tag di formattazione.
        $this->getOutput()->writeln($report, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }

    private function buildReport(CarbonImmutable $target, CarbonImmutable $today): string
    {
        $excludedAreaId = \App\Models\Area::query()
            ->where('name', self::EXCLUDED_AREA_NAME)
            ->value('id');

        $sections = [];

        [$countOpen, $section1a] = $this->sectionOrdinaryOpenNeverTaken($target, $today, $excludedAreaId);
        if ($section1a !== null) {
            $sections[] = $section1a;
        }

        [$countOverdue, $section1b] = $this->sectionOverdue($today, $excludedAreaId);
        if ($section1b !== null) {
            $sections[] = $section1b;
        }

        [$countNoUser, $section1c] = $this->sectionNoAssignedUser($excludedAreaId);
        if ($section1c !== null) {
            $sections[] = $section1c;
        }

        [$countLatePlanning, $section1d] = $this->sectionLatePlanning($today, $excludedAreaId);
        if ($section1d !== null) {
            $sections[] = $section1d;
        }

        [$countStuck, $section2] = $this->sectionStuckInProgress($today, $excludedAreaId);
        if ($section2 !== null) {
            $sections[] = $section2;
        }

        [$countClosedYesterday, $section3] = $this->sectionCreatedAndClosedYesterday($target, $excludedAreaId);
        if ($section3 !== null) {
            $sections[] = $section3;
        }

        [$countClosedWithoutReport, $section4a] = $this->sectionClosedYesterdayWithoutReport($target, $excludedAreaId);
        if ($section4a !== null) {
            $sections[] = $section4a;
        }

        $section4b = $this->sectionReportedHoursVsSchedule($target);
        if ($section4b !== null) {
            $sections[] = $section4b;
        }

        [$countPlanningToday, $section5] = $this->sectionPlanningToday($today, $excludedAreaId);
        if ($section5 !== null) {
            $sections[] = $section5;
        }

        $header = 'Manutenzioni – riepilogo di '.$this->formatItalianDate($target);

        $summaryNumbers = sprintf(
            'Aperti senza presa in carico: %d · Oltre limite: %d · Senza assegnatario: %d · Pianificazioni in ritardo: %d · In carico oltre 7 gg: %d · Chiusi ieri: %d · Pianificazioni oggi: %d',
            $countOpen,
            $countOverdue,
            $countNoUser,
            $countLatePlanning,
            $countStuck,
            $countClosedYesterday,
            $countPlanningToday,
        );

        if (empty($sections)) {
            return $header."\n".$summaryNumbers."\n\nTutto in ordine…\n";
        }

        return $header."\n".$summaryNumbers."\n\n".implode("\n\n", $sections)."\n";
    }

    /* -------------------------- Query building blocks -------------------------- */

    /**
     * Base query sui ticket non-TEST: esclude area "TEST – non intervenire" sia
     * dal legame diretto area_id/department_id sia dalla catena equipment → department → area.
     */
    private function baseQuery(?int $excludedAreaId)
    {
        $q = Intervention::query()
            ->with([
                'assignedUser:id,name',
                'area:id,name',
                'department:id,name,area_id',
                'equipment:id,name,department_id',
                'equipment.department:id,name,area_id',
                'component:id,name',
                'maintenanceRole:id,name',
            ]);

        if ($excludedAreaId !== null) {
            $q->where(function ($q) use ($excludedAreaId) {
                $q->where(function ($q) use ($excludedAreaId) {
                    $q->whereNull('area_id')
                        ->orWhere('area_id', '!=', $excludedAreaId);
                })->where(function ($q) use ($excludedAreaId) {
                    $q->whereNull('equipment_id')
                        ->orWhereHas('equipment.department', fn ($q) => $q->where('area_id', '!=', $excludedAreaId))
                        ->orWhereHas('equipment', fn ($q) => $q->whereNull('department_id'));
                });
            });
        }

        $q->where(function ($q) {
            $q->whereNull('assigned_user_id')
                ->orWhereHas('assignedUser', fn ($q) => $q->where('name', 'not like', self::EXCLUDED_USER_PREFIX.'%'));
        });

        return $q;
    }

    /* -------------------------------- Sections -------------------------------- */

    /** 1a. Ordinari Aperti, mai presi in carico, dal più vecchio. */
    private function sectionOrdinaryOpenNeverTaken(CarbonImmutable $target, CarbonImmutable $today, ?int $excludedAreaId): array
    {
        $rows = $this->baseQuery($excludedAreaId)
            ->where('tipo', 'ordinario')
            ->where('status', 'open')
            ->whereNull('preso_in_carico_at')
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET ORDINARI APERTI, MAI PRESI IN CARICO'];
        foreach ($rows as $it) {
            $days = $this->dayDiff($it->created_at, $today);
            $lines[] = $this->formatLine($it, "da {$days} ".$this->pluralGiorni($days));
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 1b. Aperti o In corso oltre il tempo limite. */
    private function sectionOverdue(CarbonImmutable $today, ?int $excludedAreaId): array
    {
        $rows = $this->baseQuery($excludedAreaId)
            ->whereIn('status', ['open', 'in_progress'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Intervention $it) => $this->isOverdue($it, $today))
            ->values();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET OLTRE IL TEMPO LIMITE'];
        foreach ($rows as $it) {
            $deadline = $this->deadlineFor($it);
            $extra = $deadline
                ? 'scaduto il '.$this->formatItalianDateTime($deadline)
                : 'scaduto';
            $lines[] = $this->formatLine($it, $extra);
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 1c. Non completati né annullati, senza persona assegnata. */
    private function sectionNoAssignedUser(?int $excludedAreaId): array
    {
        $rows = $this->baseQuery($excludedAreaId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNull('assigned_user_id')
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET SENZA ASSEGNATARIO'];
        foreach ($rows as $it) {
            $lines[] = $this->formatLine($it);
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 1d. Pianificazioni in stato Pianificato o Aperto con data < oggi. */
    private function sectionLatePlanning(CarbonImmutable $today, ?int $excludedAreaId): array
    {
        $rows = $this->baseQuery($excludedAreaId)
            ->where('tipo', 'pianificazione')
            ->whereIn('status', ['planned', 'open'])
            ->whereNotNull('scheduled_date')
            ->whereDate('scheduled_date', '<', $today->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['PIANIFICAZIONI IN RITARDO'];
        foreach ($rows as $it) {
            $extra = 'prevista per il '.$this->formatItalianDate(CarbonImmutable::parse($it->scheduled_date));
            $lines[] = $this->formatLine($it, $extra);
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 2. In corso presi in carico da oltre 7 giorni. */
    private function sectionStuckInProgress(CarbonImmutable $today, ?int $excludedAreaId): array
    {
        $cutoff = $today->subDays(7)->setTimezone('UTC');

        $rows = $this->baseQuery($excludedAreaId)
            ->where('status', 'in_progress')
            ->whereNotNull('preso_in_carico_at')
            ->where('preso_in_carico_at', '<', $cutoff)
            ->orderBy('preso_in_carico_at')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET IN CARICO DA OLTRE 7 GIORNI'];
        foreach ($rows as $it) {
            $days = $this->dayDiff($it->preso_in_carico_at, $today);
            $lines[] = $this->formatLine($it, "in carico da {$days} ".$this->pluralGiorni($days));
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 3. Ticket creati ieri e chiusi ieri (con conteggio per manutentore). */
    private function sectionCreatedAndClosedYesterday(CarbonImmutable $target, ?int $excludedAreaId): array
    {
        [$startUtc, $endUtc] = $this->utcBoundsForLocalDay($target);

        $rows = $this->baseQuery($excludedAreaId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->whereBetween('completed_at', [$startUtc, $endUtc])
            ->with(['closedBy:id,name'])
            ->orderBy('completed_at')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET CREATI E CHIUSI IERI'];
        foreach ($rows as $it) {
            $lines[] = $this->formatLine($it);
        }

        // Conteggio per chi ha chiuso (fallback all'assegnatario se manca).
        $counts = [];
        foreach ($rows as $it) {
            $who = $it->closedBy?->name ?? $it->assignedUser?->name ?? 'sconosciuto';
            $counts[$who] = ($counts[$who] ?? 0) + 1;
        }
        arsort($counts);
        $lines[] = '  Chiusi per manutentore:';
        foreach ($counts as $name => $count) {
            $lines[] = "  - {$name}: {$count}";
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 4a. Ticket chiusi ieri senza rapportini. */
    private function sectionClosedYesterdayWithoutReport(CarbonImmutable $target, ?int $excludedAreaId): array
    {
        [$startUtc, $endUtc] = $this->utcBoundsForLocalDay($target);

        $rows = $this->baseQuery($excludedAreaId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startUtc, $endUtc])
            ->whereDoesntHave('reports')
            ->orderBy('completed_at')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['TICKET CHIUSI IERI SENZA RAPPORTINI'];
        foreach ($rows as $it) {
            $lines[] = $this->formatLine($it);
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /** 4b. Ore registrate dai manutentori vs piano orario. */
    private function sectionReportedHoursVsSchedule(CarbonImmutable $target): ?string
    {
        $manutentori = User::query()
            ->where('role', 'manutentore')
            ->where('active', true)
            ->where('name', 'not like', self::EXCLUDED_USER_PREFIX.'%')
            ->with(['workScheduleSlots'])
            ->orderBy('name')
            ->get();

        if ($manutentori->isEmpty()) {
            return null;
        }

        $dateStr = $target->toDateString();

        $reportsByUser = Report::query()
            ->whereDate('report_date', $dateStr)
            ->whereIn('user_id', $manutentori->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $underReported = [];       // senza rapportini oppure sotto di oltre tolleranza
        $extraOrNonWorking = [];   // rapportini in giorno non lavorativo o più ore del previsto
        $missingSchedule = [];     // ieri manca il piano orario

        foreach ($manutentori as $user) {
            /** @var Collection $userReports */
            $userReports = $reportsByUser->get($user->id, collect());
            $registeredMinutes = $userReports->sum(fn (Report $r) => $this->reportDurationMinutes($r));

            $hasSchedule = $this->userHasAnyScheduleForDate($user, $target);
            if (! $hasSchedule) {
                $missingSchedule[] = [
                    'name' => $user->name,
                    'registered' => $registeredMinutes,
                ];

                continue;
            }

            $expectedMinutes = $this->expectedWorkMinutes($user, $target);

            if ($expectedMinutes <= 0) {
                // Giorno non lavorativo: segnalo solo se ci sono rapportini.
                if ($registeredMinutes > 0) {
                    $extraOrNonWorking[] = [
                        'name' => $user->name,
                        'registered' => $registeredMinutes,
                        'expected' => 0,
                        'reason' => 'giorno non lavorativo',
                    ];
                }

                continue;
            }

            if ($registeredMinutes === 0) {
                $underReported[] = [
                    'name' => $user->name,
                    'registered' => 0,
                    'expected' => $expectedMinutes,
                    'missing_all' => true,
                ];

                continue;
            }

            $delta = $expectedMinutes - $registeredMinutes;
            if ($delta > self::TOLERANCE_MINUTES) {
                $underReported[] = [
                    'name' => $user->name,
                    'registered' => $registeredMinutes,
                    'expected' => $expectedMinutes,
                    'missing_all' => false,
                ];
            } elseif ($registeredMinutes > $expectedMinutes) {
                $extraOrNonWorking[] = [
                    'name' => $user->name,
                    'registered' => $registeredMinutes,
                    'expected' => $expectedMinutes,
                    'reason' => 'più ore del previsto',
                ];
            }
        }

        if (empty($underReported) && empty($extraOrNonWorking) && empty($missingSchedule)) {
            return null;
        }

        $lines = ['ORE REGISTRATE DAI MANUTENTORI'];

        foreach ($underReported as $u) {
            if ($u['missing_all']) {
                $lines[] = '- '.$u['name'].': nessun rapportino (previste '.$this->formatHoursMinutes($u['expected']).')';
            } else {
                $lines[] = '- '.$u['name'].': '.$this->formatHoursMinutes($u['registered']).' su '.$this->formatHoursMinutes($u['expected']);
            }
        }

        if (! empty($extraOrNonWorking)) {
            $lines[] = '  Segnalazioni senza giudizio:';
            foreach ($extraOrNonWorking as $u) {
                $lines[] = '  - '.$u['name'].': '.$this->formatHoursMinutes($u['registered'])
                    .($u['expected'] > 0 ? ' su '.$this->formatHoursMinutes($u['expected']) : '')
                    .' ('.$u['reason'].')';
            }
        }

        if (! empty($missingSchedule)) {
            $lines[] = '  Piano orario non impostato per ieri:';
            foreach ($missingSchedule as $u) {
                $lines[] = '  - '.$u['name'].': '.$this->formatHoursMinutes($u['registered']).' registrate';
            }
        }

        return implode("\n", $lines);
    }

    /** 5. Pianificazioni con data = oggi. */
    private function sectionPlanningToday(CarbonImmutable $today, ?int $excludedAreaId): array
    {
        $rows = $this->baseQuery($excludedAreaId)
            ->where('tipo', 'pianificazione')
            ->whereDate('scheduled_date', $today->toDateString())
            ->orderBy('scheduled_start_time')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [0, null];
        }

        $lines = ['PIANIFICAZIONI DI OGGI'];
        foreach ($rows as $it) {
            $lines[] = $this->formatLine($it);
        }

        return [$rows->count(), implode("\n", $lines)];
    }

    /**
     * Giorni interi trascorsi tra $from e $to, letti sul calendario Europe/Rome
     * per evitare sfalsamenti di fuso dovuti allo storage UTC.
     */
    private function dayDiff($from, CarbonImmutable $to): int
    {
        $fromRome = CarbonImmutable::parse($from)->setTimezone(self::TZ)->startOfDay();
        $toRome = $to->setTimezone(self::TZ)->startOfDay();

        return (int) $fromRome->diffInDays($toRome, true);
    }

    /**
     * Confini UTC [inizio, fine] del giorno locale $date. Serve per interrogare
     * colonne timestamp (store in UTC) con la precisione del fuso Europe/Rome.
     */
    private function utcBoundsForLocalDay(CarbonImmutable $date): array
    {
        $local = $date->setTimezone(self::TZ);

        return [
            $local->startOfDay()->setTimezone('UTC'),
            $local->endOfDay()->setTimezone('UTC'),
        ];
    }

    /* ------------------------------ Helpers ------------------------------ */

    private function isOverdue(Intervention $it, CarbonImmutable $today): bool
    {
        $deadline = $this->deadlineFor($it);
        if (! $deadline) {
            return false;
        }

        return CarbonImmutable::now()->greaterThan($deadline);
    }

    private function deadlineFor(Intervention $it): ?CarbonImmutable
    {
        if ($it->tipo === 'pianificazione' || $it->priority === 'fixed_date') {
            if (! $it->scheduled_date) {
                return null;
            }
            $date = CarbonImmutable::parse($it->scheduled_date)->toDateString();
            $time = $it->scheduled_start_time ?: '08:00:00';

            return CarbonImmutable::parse($date.' '.$time);
        }

        if (! $it->created_at) {
            return null;
        }

        $hours = self::ORDINARY_DEADLINE_HOURS[$it->priority] ?? null;
        if ($hours === null) {
            return null;
        }

        return CarbonImmutable::parse($it->created_at)->addHours($hours);
    }

    /**
     * Linea: "- #ID titolo – destinazione – assegnatario – [extra] – URL"
     */
    private function formatLine(Intervention $it, ?string $extra = null): string
    {
        $parts = [
            '#'.$it->id.' '.($it->title ?: 'Ticket'),
            $this->destinationFor($it),
            $this->assigneeFor($it),
        ];
        if ($extra) {
            $parts[] = $extra;
        }
        $parts[] = $this->urlFor($it);

        return '- '.implode(' – ', array_filter($parts, fn ($p) => $p !== '' && $p !== null));
    }

    private function destinationFor(Intervention $it): string
    {
        if ($it->tipo === 'pianificazione' && $it->equipment) {
            $txt = $it->equipment->name;
            if ($it->component) {
                $txt .= ' / '.$it->component->name;
            }

            return $txt;
        }

        $parts = [];
        if ($it->area) {
            $parts[] = $it->area->name;
        }
        if ($it->department) {
            $parts[] = $it->department->name;
        }
        if (! empty($parts)) {
            return implode(' / ', $parts);
        }

        if ($it->equipment) {
            return $it->equipment->name;
        }

        return 'destinazione non specificata';
    }

    private function assigneeFor(Intervention $it): string
    {
        if ($it->assignedUser) {
            return $it->assignedUser->name;
        }
        if ($it->maintenanceRole) {
            return 'solo specializzazione '.$it->maintenanceRole->name;
        }

        return 'non assegnato';
    }

    private function urlFor(Intervention $it): string
    {
        try {
            return URL::route('interventions.show', $it);
        } catch (\Throwable $e) {
            return rtrim((string) config('app.url'), '/').'/interventions/'.$it->id;
        }
    }

    /* ----------------------------- Reports / hours ----------------------------- */

    private function reportDurationMinutes(Report $r): int
    {
        if ($r->duration_minutes !== null && $r->duration_minutes !== '') {
            return (int) $r->duration_minutes;
        }
        if ($r->start_time && $r->end_time) {
            $date = $r->report_date ? CarbonImmutable::parse($r->report_date)->toDateString() : '2000-01-01';
            $start = CarbonImmutable::parse($date.' '.$r->start_time);
            $end = CarbonImmutable::parse($date.' '.$r->end_time);
            if ($end->lessThan($start)) {
                $end = $end->addDay();
            }

            return (int) $start->diffInMinutes($end, true);
        }

        return 0;
    }

    /**
     * Minuti lavorativi previsti dal piano orario per quel giorno (slot type=lavorativo),
     * al netto di ferie/riposi concreti. Pausa pranzo scala dalle ore lavorative.
     */
    private function expectedWorkMinutes(User $user, CarbonImmutable $date): int
    {
        if ($this->userIsOffOn($user, $date)) {
            return 0;
        }

        $workMinutes = 0;
        $pausaMinutes = 0;

        foreach ($user->workScheduleSlots as $slot) {
            if (! $this->slotAppliesTo($slot, $date)) {
                continue;
            }
            if (! $slot->start_time || ! $slot->end_time) {
                continue;
            }
            $minutes = $this->slotDurationMinutes($slot);
            if ($slot->type === 'lavorativo') {
                $workMinutes += $minutes;
            } elseif ($slot->type === 'pausa_pranzo') {
                $pausaMinutes += $minutes;
            }
        }

        return max(0, $workMinutes - $pausaMinutes);
    }

    private function userIsOffOn(User $user, CarbonImmutable $date): bool
    {
        foreach ($user->workScheduleSlots as $slot) {
            if (! in_array($slot->type, ['ferie', 'riposi'], true)) {
                continue;
            }
            if ($this->slotAppliesTo($slot, $date)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Esiste almeno uno slot (di qualsiasi tipo) che tocca la data? Serve per
     * distinguere "giorno non lavorativo pianificato" da "piano orario mancante".
     */
    private function userHasAnyScheduleForDate(User $user, CarbonImmutable $date): bool
    {
        foreach ($user->workScheduleSlots as $slot) {
            if ($this->slotAppliesTo($slot, $date)) {
                return true;
            }
        }

        return false;
    }

    private function slotAppliesTo(WorkScheduleSlot $slot, CarbonImmutable $date): bool
    {
        $dateStr = $date->toDateString();

        if ($slot->is_recurring) {
            if ((int) $slot->day_of_week !== $date->dayOfWeek) {
                return false;
            }
            if ($slot->date_from && $date->lt(CarbonImmutable::parse($slot->date_from)->startOfDay())) {
                return false;
            }
            if ($slot->date_to && $date->gt(CarbonImmutable::parse($slot->date_to)->endOfDay())) {
                return false;
            }

            return true;
        }

        if ($slot->date && CarbonImmutable::parse($slot->date)->toDateString() === $dateStr) {
            return true;
        }

        if ($slot->date_from && $slot->date_to) {
            $from = CarbonImmutable::parse($slot->date_from)->startOfDay();
            $to = CarbonImmutable::parse($slot->date_to)->endOfDay();
            if ($date->between($from, $to)) {
                return true;
            }
        }

        return false;
    }

    private function slotDurationMinutes(WorkScheduleSlot $slot): int
    {
        if (! $slot->start_time || ! $slot->end_time) {
            return 0;
        }
        $start = CarbonImmutable::parse('2000-01-01 '.$slot->start_time);
        $end = CarbonImmutable::parse('2000-01-01 '.$slot->end_time);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }

    /* ----------------------------- Formatting ----------------------------- */

    private function formatItalianDate(CarbonImmutable $d): string
    {
        return self::DAY_NAMES_IT[$d->dayOfWeek].' '.$d->day.' '.self::MONTH_NAMES_IT[$d->month].' '.$d->year;
    }

    private function formatItalianDateTime(CarbonImmutable $d): string
    {
        return $this->formatItalianDate($d).' alle '.$d->format('H:i');
    }

    private function formatHoursMinutes(int $minutes): string
    {
        $h = intdiv(max(0, $minutes), 60);
        $m = max(0, $minutes) % 60;
        if ($m === 0) {
            return $h.' h';
        }

        return $h.' h '.str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    private function pluralGiorni(int $n): string
    {
        return $n === 1 ? 'giorno' : 'giorni';
    }

    /* -------------------------------- Email -------------------------------- */

    private function sendEmail(string $to, CarbonImmutable $target, string $report): int
    {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Indirizzo email non valido: '.$to);

            return self::FAILURE;
        }

        $subject = 'Manutenzioni – riepilogo di '.$this->formatItalianDate($target);

        Mail::raw($report, function ($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });

        $this->info('Report inviato a '.$to);

        return self::SUCCESS;
    }
}
