<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Department;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\InterventionTransfer;
use App\Models\Media;
use App\Models\User;
use App\Notifications\CollaborationRequestedNotification;
use App\Notifications\InterventionTransferredNotification;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public const CHIPS = [
        'all' => 'Tutti',
        'overdue' => 'Scaduti',
        'today' => 'Oggi',
        'high' => 'Alta priorità',
        'low' => 'Bassa priorità',
        'in_progress' => 'In carico',
        'completed' => 'Chiusi',
        'cancelled' => 'Sospesi',
        'planned' => 'Programmati',
        'assigned' => 'Presi in carico',
    ];

    public function index(Request $request): View
    {
        $user = Auth::user();
        $deptIds = $user->departments()->pluck('departments.id');
        $filter = $request->get('filter', 'all');
        $q = trim((string) $request->get('q', ''));

        if (! array_key_exists($filter, self::CHIPS)) {
            $filter = 'all';
        }

        $query = Intervention::with([
            'equipment.department.area',
            'area',
            'department',
            'maintenanceRole',
            'assignedUser',
            'creator:id,name',
            'collaborations' => fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', InterventionCollaboration::STATUS_ACCEPTED),
            'activeCollaborations.user:id,name',
            'reports' => fn ($q) => $q->where('user_id', $user->id),
            'activeReschedules.user:id,name',
        ])
            ->withCount('transfers')
            ->where(function ($q) use ($user, $deptIds) {
                // I manutentori vedono SOLO i ticket a loro assegnati o in cui
                // sono collaboratori accettati. Gli operatori (e altri ruoli)
                // vedono anche tutti i ticket dei dipartimenti a loro assegnati
                // e quelli da loro creati.
                $q->where('assigned_user_id', $user->id);

                $q->orWhereHas('collaborations', fn ($c) => $c
                    ->where('user_id', $user->id)
                    ->where('status', InterventionCollaboration::STATUS_ACCEPTED));

                if ($user->role !== 'manutentore') {
                    // Chi ha aperto il ticket lo vede sempre.
                    $q->orWhere('created_by', $user->id);

                    if ($deptIds->isNotEmpty()) {
                        $q->orWhere(function ($q2) use ($deptIds) {
                            $q2->whereIn('department_id', $deptIds)
                                ->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
                        });
                    }
                }
            });

        // Nasconde ticket sospesi/rinviati fino alla data di ripresa, tranne quando
        // l'utente filtra esplicitamente "sospesi".
        if ($filter !== 'cancelled') {
            $query->where(function ($q) {
                $q->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<=', today());
            });
        }

        switch ($filter) {
            case 'today':
                // Aperti oggi e/o presi in carico oggi.
                $query->where(function ($q) {
                    $q->whereDate('created_at', today())
                        ->orWhereDate('preso_in_carico_at', today());
                });
                break;

            case 'high':
                $query->whereIn('priority', ['high', 'medium'])
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;

            case 'low':
                $query->whereIn('priority', ['low', 'fixed_date'])
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;

            case 'in_progress':
                $query->where('status', 'in_progress');
                break;

            case 'completed':
                // "Chiusi" = solo i ticket su cui l'utente è davvero coinvolto:
                // assegnatario, creatore, collaboratore accettato o autore di un rapportino.
                // Senza questo scope un manutentore vedrebbe tutti i chiusi del suo
                // dipartimento (e per via del reject finale, non vedrebbe i propri).
                $query->where('status', 'completed')
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_user_id', $user->id)
                            ->orWhere('created_by', $user->id)
                            ->orWhereHas('collaborations', fn ($c) => $c
                                ->where('user_id', $user->id)
                                ->where('status', InterventionCollaboration::STATUS_ACCEPTED))
                            ->orWhereHas('reports', fn ($r) => $r->where('user_id', $user->id));
                    });
                break;

            case 'cancelled':
                // "Sospesi" = ticket rinviati: con suspended_until futura
                // o con un rapportino non finale che sposta next_work_date oltre oggi.
                $query->where(function ($q) {
                    $q->where('suspended_until', '>', today())
                        ->orWhereHas('activeReschedules');
                });
                break;

            case 'planned':
                $query->where('tipo', 'pianificazione')
                    ->whereDate('scheduled_date', '>', today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;

            case 'assigned':
                $query->whereNotNull('assigned_user_id');
                break;
        }

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($qq) use ($like) {
                $qq->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('area', fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('department', fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('equipment', fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('assignedUser', fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('maintenanceRole', fn ($s) => $s->where('name', 'like', $like));
            });
        }

        if ($filter === 'all') {
            // Richiesta: "Tutti" ordina per ID desc, niente priorità.
            $interventions = $query->orderBy('id', 'desc')->get();
        } else {
            $interventions = $query
                ->orderByRaw("FIELD(status, 'in_progress', 'open', 'planned', 'completed', 'cancelled')")
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'fixed_date', 'low')")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // In /tickets mostriamo anche i ticket con next_work_date futura (il badge "rinviato al …"
        // già li segnala). Nascondiamo i ticket dove l'utente ha chiuso definitivamente il suo
        // percorso — tranne nel filtro "Chiusi" (dove devono comparire proprio quelli) e tranne
        // nel filtro "Tutti" se la chiusura è recente, così l'utente non li perde subito dopo
        // aver firmato il rapportino finale.
        if ($filter !== 'completed') {
            $recentCutoff = now()->subDays(7);
            $interventions = $interventions->reject(function ($i) use ($filter, $recentCutoff) {
                $finalReports = $i->reports->where('is_final', true);
                if ($finalReports->isEmpty()) {
                    return false;
                }
                if ($filter === 'all' && $finalReports->contains(fn ($r) => $r->created_at?->greaterThanOrEqualTo($recentCutoff))) {
                    return false;
                }

                return true;
            })->values();
        }

        if ($filter === 'overdue') {
            $interventions = $interventions->filter(fn ($i) => $i->is_overdue)->values();
        }

        [$quickAreas, $quickDepartments, $quickEquipments, $quickMaintenanceRoles, $quickManutentori]
            = HomeController::quickOpenScope($user);

        return view('manutentore.tickets.index', [
            'interventions' => $interventions,
            'filter' => $filter,
            'q' => $q,
            'chips' => self::CHIPS,
            'quickAreas' => $quickAreas,
            'quickDepartments' => $quickDepartments,
            'quickEquipments' => $quickEquipments,
            'quickMaintenanceRoles' => $quickMaintenanceRoles,
            'quickManutentori' => $quickManutentori,
        ]);
    }

    public function showJson(Intervention $intervention): JsonResponse
    {
        $this->authorizeVisibility($intervention);

        $intervention->load([
            'assignedUser',
            'transfers.fromUser',
            'transfers.toUser',
            'transfers.initiatedBy',
            'collaborations.user',
            'collaborations.requestedBy',
            'reports.user',
            'reports.media',
            'activeReschedules.user:id,name',
        ]);

        $nextReschedule = $intervention->activeReschedules
            ->sortBy(fn ($r) => $r->next_work_date->timestamp)
            ->first();

        $me = Auth::user();
        $deadline = $intervention->deadline;
        $isOverdue = $intervention->is_overdue;

        $mine = $intervention->assigned_user_id === $me->id;
        $unassigned = is_null($intervention->assigned_user_id);
        $iAmAcceptedCollaborator = $intervention->collaborations
            ->where('user_id', $me->id)
            ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->isNotEmpty();

        $pendingCollabForMe = $intervention->collaborations
            ->firstWhere(fn ($c) => $c->user_id === $me->id && $c->status === InterventionCollaboration::STATUS_PENDING);

        $myReports = $intervention->reports->where('user_id', $me->id);
        $myFinalReport = $myReports->firstWhere('is_final', true);
        $myLastReport = $myReports->sortByDesc('created_at')->first();
        $iHaveFinal = (bool) $myFinalReport;

        // Chi deve ancora scrivere il rapportino: assegnatario + collaboratori accettati
        $requiredReporterIds = collect([$intervention->assigned_user_id])
            ->merge($intervention->collaborations
                ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
                ->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        $reportsByUser = $intervention->reports->groupBy('user_id');

        $reportStatuses = $requiredReporterIds->map(function ($userId) use ($reportsByUser, $intervention) {
            $user = $userId === $intervention->assigned_user_id
                ? $intervention->assignedUser
                : $intervention->collaborations->firstWhere('user_id', $userId)?->user;
            $userReports = $reportsByUser->get($userId, collect());
            $finalReport = $userReports->firstWhere('is_final', true);
            $lastReport = $userReports->sortByDesc('created_at')->first();

            return [
                'user_id' => $userId,
                'name' => $user?->name,
                'initials' => $this->initials($user?->name ?? ''),
                'role' => $userId === $intervention->assigned_user_id ? 'Assegnatario' : 'Collaboratore',
                'count' => $userReports->count(),
                'has_final' => (bool) $finalReport,
                'next_work_date' => $finalReport ? null : $lastReport?->next_work_date?->isoFormat('D MMM YYYY'),
            ];
        })->values();

        $area = $intervention->area ?? $intervention->equipment?->department?->area;
        $department = $intervention->department ?? $intervention->equipment?->department;

        $transfers = $intervention->transfers->map(fn ($t) => [
            'id' => $t->id,
            'event' => 'transfer',
            'from_user_name' => $t->fromUser?->name ?? '—',
            'to_user_name' => $t->toUser?->name,
            'initiated_by' => $t->initiatedBy?->name,
            'reason' => $t->reason,
            'at' => $t->transferred_at?->isoFormat('D MMM YYYY · HH:mm'),
            'at_sort' => $t->transferred_at?->timestamp,
        ]);

        $collabEvents = $intervention->collaborations->map(fn ($c) => [
            'id' => $c->id,
            'event' => 'collaboration_'.$c->status,
            'user_name' => $c->user?->name,
            'requested_by' => $c->requestedBy?->name,
            'message' => $c->message,
            'status' => $c->status,
            'at' => ($c->responded_at ?? $c->requested_at ?? $c->created_at)?->isoFormat('D MMM YYYY · HH:mm'),
            'at_sort' => ($c->responded_at ?? $c->requested_at ?? $c->created_at)?->timestamp,
        ]);

        $reportEvents = $intervention->reports->map(fn ($r) => [
            'id' => $r->id,
            'event' => 'report_'.($r->status ?? 'draft'),
            'user_name' => $r->user?->name,
            'status' => $r->status,
            'duration' => $r->duration_minutes
                ? $this->formatDuration((int) $r->duration_minutes)
                : null,
            'activities' => $r->activities,
            'notes' => $r->notes,
            'report_date' => $r->report_date?->isoFormat('D MMM YYYY'),
            'is_final' => (bool) $r->is_final,
            'next_work_date' => $r->next_work_date?->isoFormat('D MMM YYYY'),
            'media' => $r->media->map(fn ($m) => [
                'name' => $m->file_name,
                'type' => $m->file_type,
                'is_image' => str_contains((string) $m->file_type, 'image'),
                'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($m->file_path),
            ])->values(),
            'at' => $r->created_at?->isoFormat('D MMM YYYY · HH:mm'),
            'at_sort' => $r->created_at?->timestamp,
        ]);

        $history = $transfers
            ->concat($collabEvents)
            ->concat($reportEvents)
            ->sortByDesc('at_sort')
            ->values();

        $collaborators = $intervention->collaborations
            ->whereIn('status', [InterventionCollaboration::STATUS_ACCEPTED, InterventionCollaboration::STATUS_PENDING])
            ->map(fn ($c) => [
                'id' => $c->id,
                'user_id' => $c->user_id,
                'name' => $c->user?->name,
                'status' => $c->status,
                'initials' => $this->initials($c->user?->name ?? ''),
            ])
            ->values();

        return response()->json([
            'id' => $intervention->id,
            'code' => '#'.$intervention->id,
            'title' => $intervention->title,
            'description' => $intervention->description,
            'tipo' => $intervention->tipo,
            'tipo_label' => $intervention->tipo === 'pianificazione' ? 'Pianificato' : 'Libero',
            'status' => $intervention->status,
            'status_label' => [
                'open' => 'Aperto',
                'planned' => 'Pianificato',
                'in_progress' => 'In carico',
                'completed' => 'Chiuso',
                'cancelled' => 'Sospeso',
            ][$intervention->status] ?? $intervention->status,
            'priority' => $intervention->priority,
            'priority_label' => [
                'high' => 'Alta',
                'medium' => 'Media',
                'low' => 'Bassa',
                'fixed_date' => 'Data fissa',
            ][$intervention->priority] ?? $intervention->priority,
            'created_at' => $intervention->created_at?->isoFormat('D MMM YYYY · HH:mm'),
            'scheduled_date' => $intervention->scheduled_date?->isoFormat('dddd D MMM YYYY'),
            'scheduled_start' => $intervention->scheduled_start_time,
            'duration_minutes' => $intervention->estimated_duration_minutes,
            'area' => $area?->name,
            'department' => $department?->name,
            'equipment' => $intervention->equipment?->name,
            'maintenance_role' => $intervention->maintenanceRole?->name,
            'assigned_user' => $intervention->assignedUser
                ? array_merge($intervention->assignedUser->only(['id', 'name']),
                    ['initials' => $this->initials($intervention->assignedUser->name)])
                : null,
            'mine' => $mine,
            'i_have_final' => $iHaveFinal,
            'my_next_work_date' => $myFinalReport ? null : $myLastReport?->next_work_date?->isoFormat('D MMM YYYY'),
            'reschedule' => $nextReschedule ? [
                'date' => $nextReschedule->next_work_date->isoFormat('D MMM YYYY'),
                'user_name' => $nextReschedule->user?->name,
            ] : null,
            'unassigned' => $unassigned,
            'is_overdue' => $isOverdue,
            'overdue_since' => $isOverdue && $deadline
                ? $deadline->diffForHumans(['parts' => 2, 'syntax' => CarbonInterface::DIFF_ABSOLUTE])
                : null,
            'deadline_at' => $deadline?->isoFormat('D MMM · HH:mm'),
            'notes' => $intervention->notes,
            'collaborators' => $collaborators,
            'report_statuses' => $reportStatuses,
            'my_reports_count' => $myReports->count(),
            'history' => $history,
            'pending_request' => $pendingCollabForMe ? [
                'id' => $pendingCollabForMe->id,
                'requested_by' => $pendingCollabForMe->requestedBy?->name,
                'message' => $pendingCollabForMe->message,
                'respond_url' => route('m.collaborations.respond', $pendingCollabForMe->id),
            ] : null,
            'suspended_until' => $intervention->suspended_until?->toDateString(),
            'suspended_until_label' => $intervention->suspended_until?->isoFormat('D MMM YYYY'),
            'urls' => [
                'take_charge' => route('interventions.take-charge', $intervention),
                'details' => route('interventions.show', $intervention),
                'transfer' => route('m.interventions.transfer', $intervention),
                'collaboration' => route('m.interventions.collaboration', $intervention),
                'candidates' => route('m.interventions.candidates', $intervention),
                'report_store' => route('m.reports.store', $intervention),
                'edit' => route('m.tickets.edit', $intervention),
                'destroy' => route('m.tickets.destroy', $intervention),
            ],
            'actions' => [
                'can_take_charge' => $me->role === 'manutentore'
                                        && ($unassigned || $mine)
                                        && $intervention->preso_in_carico_at === null
                                        && ! in_array($intervention->status, ['completed', 'cancelled']),
                'can_create_report' => ($mine || $iAmAcceptedCollaborator)
                                        && $intervention->status === 'in_progress'
                                        && ! $iHaveFinal,
                'can_transfer' => $mine && ! $iHaveFinal && $intervention->preso_in_carico_at !== null && ! in_array($intervention->status, ['completed', 'cancelled']),
                'can_collaborate' => $mine && ! $iHaveFinal && $intervention->preso_in_carico_at !== null && ! in_array($intervention->status, ['completed', 'cancelled']),
                'can_edit' => $intervention->canBeEditedBy($me),
                'can_delete' => $intervention->canBeDeletedBy($me),
            ],
        ]);
    }

    public function similarOpenJson(Request $request): JsonResponse
    {
        $user = Auth::user();
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 3) {
            return response()->json(['items' => []]);
        }

        $deptIds = $user->departments()->pluck('departments.id');
        if ($deptIds->isEmpty()) {
            return response()->json(['items' => []]);
        }

        $like = '%'.$q.'%';

        $items = Intervention::with([
            'area:id,name',
            'department:id,name',
            'equipment:id,name,department_id',
            'equipment.department:id,name,area_id',
            'equipment.department.area:id,name',
            'assignedUser:id,name',
        ])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('title', 'like', $like)
            ->where(function ($w) use ($deptIds) {
                $w->whereIn('department_id', $deptIds)
                    ->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'code' => '#'.$i->id,
                'title' => $i->title,
                'status' => $i->status,
                'status_label' => [
                    'open' => 'Aperto',
                    'planned' => 'Pianificato',
                    'in_progress' => 'In carico',
                ][$i->status] ?? $i->status,
                'priority' => $i->priority,
                'area' => $i->area?->name ?? $i->equipment?->department?->area?->name,
                'department' => $i->department?->name ?? $i->equipment?->department?->name,
                'equipment' => $i->equipment?->name,
                'assigned_user' => $i->assignedUser?->name,
                'created_at' => $i->created_at?->isoFormat('D MMM YYYY'),
            ]);

        return response()->json(['items' => $items]);
    }

    public function candidatesJson(Request $request, Intervention $intervention): JsonResponse
    {
        $this->authorizeVisibility($intervention);
        $purpose = $request->get('purpose', 'transfer');

        // Su richiesta: trasferisci/collabora può scegliere fra tutti i manutentori
        // (indipendenti da specializzazione e zona), ordinati alfabeticamente.
        $query = User::where('role', 'manutentore')
            ->where('active', true)
            ->where('id', '!=', Auth::id());

        if ($purpose === 'transfer' && $intervention->assigned_user_id) {
            $query->where('id', '!=', $intervention->assigned_user_id);
        }

        if ($purpose === 'collaboration') {
            $busyIds = $intervention->collaborations()
                ->whereIn('status', [
                    InterventionCollaboration::STATUS_PENDING,
                    InterventionCollaboration::STATUS_ACCEPTED,
                ])
                ->pluck('user_id')
                ->push($intervention->assigned_user_id)
                ->filter()
                ->unique()
                ->values();
            if ($busyIds->isNotEmpty()) {
                $query->whereNotIn('id', $busyIds);
            }
        }

        $users = $query->with('workScheduleSlots')->orderBy('name')->get();

        // Per collaborazione: solo utenti attualmente in turno
        //        if ($purpose === 'collaboration') {
        //            $users = $users->filter(fn ($u) => $u->isOnShift())->values();
        //        }

        $candidates = $users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => ucfirst($u->role),
            'initials' => $this->initials($u->name),
        ])->values();

        return response()->json(['candidates' => $candidates]);
    }

    public function transfer(Request $request, Intervention $intervention): JsonResponse
    {
        $me = Auth::user();

        if ($intervention->assigned_user_id !== $me->id && $me->role !== 'admin') {
            return response()->json(['ok' => false, 'message' => 'Solo l\'assegnatario corrente può trasferire il ticket.'], 403);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'Ticket chiuso, non trasferibile.'], 422);
        }

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ((int) $data['to_user_id'] === (int) $intervention->assigned_user_id) {
            return response()->json(['ok' => false, 'message' => 'Il destinatario coincide con l\'assegnatario attuale.'], 422);
        }

        $toUser = User::findOrFail($data['to_user_id']);

        $fromUserId = $intervention->assigned_user_id;

        InterventionTransfer::create([
            'intervention_id' => $intervention->id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUser->id,
            'initiated_by_user_id' => $me->id,
            'reason' => $data['reason'] ?? null,
            'transferred_at' => now(),
        ]);

        $intervention->update([
            'assigned_user_id' => $toUser->id,
            'status' => in_array($intervention->status, ['open', 'planned']) ? 'in_progress' : $intervention->status,
            'preso_in_carico_at' => null,
        ]);

        InterventionActivityLogger::log($intervention, InterventionLogActions::TRANSFERRED, array_filter([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUser->id,
            'reason' => $data['reason'] ?? null,
        ], fn ($v) => $v !== null));

        $toUser->notify(new InterventionTransferredNotification($intervention->fresh(), $me, $data['reason'] ?? null));

        return response()->json([
            'ok' => true,
            'message' => 'Ticket trasferito a '.$toUser->name.'.',
        ]);
    }

    public function requestCollaboration(Request $request, Intervention $intervention): JsonResponse
    {
        $me = Auth::user();

        $isAssignee = $intervention->assigned_user_id === $me->id;
        $isCollaborator = $intervention->collaborations()
            ->where('user_id', $me->id)
            ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->exists();

        if (! $isAssignee && ! $isCollaborator && $me->role !== 'admin') {
            return response()->json(['ok' => false, 'message' => 'Non hai i permessi per richiedere collaborazione su questo ticket.'], 403);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'Ticket chiuso.'], 422);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        if ((int) $data['user_id'] === (int) $intervention->assigned_user_id) {
            return response()->json(['ok' => false, 'message' => 'Utente già assegnatario.'], 422);
        }

        $existing = $intervention->collaborations()
            ->where('user_id', $data['user_id'])
            ->whereIn('status', [
                InterventionCollaboration::STATUS_PENDING,
                InterventionCollaboration::STATUS_ACCEPTED,
            ])
            ->exists();

        if ($existing) {
            return response()->json(['ok' => false, 'message' => 'Richiesta già presente per questo utente.'], 422);
        }

        $collab = InterventionCollaboration::create([
            'intervention_id' => $intervention->id,
            'user_id' => $data['user_id'],
            'requested_by_user_id' => $me->id,
            'status' => InterventionCollaboration::STATUS_PENDING,
            'message' => $data['message'] ?? null,
            'requested_at' => now(),
        ]);

        $target = User::find($data['user_id']);
        $target->notify(new CollaborationRequestedNotification($collab));

        InterventionActivityLogger::log($intervention, InterventionLogActions::COLLABORATION_REQUESTED, [
            'collaboration_id' => $collab->id,
            'target_user_id' => $target->id,
            'target_user_name' => $target->name,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Richiesta inviata a '.$target->name.'.',
        ]);
    }

    public function suspend(Request $request, Intervention $intervention): JsonResponse
    {
        $me = Auth::user();

        if ($intervention->assigned_user_id !== $me->id && $me->role !== 'admin') {
            return response()->json(['ok' => false, 'message' => 'Solo l\'assegnatario può sospendere il ticket.'], 403);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'Ticket già chiuso o sospeso.'], 422);
        }

        $data = $request->validate([
            'until' => ['required', 'date', 'after_or_equal:tomorrow'],
        ], [
            'until.after_or_equal' => 'La data di ripresa deve essere futura.',
        ]);

        $intervention->update([
            'status' => 'cancelled',
            'suspended_until' => $data['until'],
        ]);

        InterventionActivityLogger::log($intervention, InterventionLogActions::SUSPENDED, [
            'suspended_until' => $data['until'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Ticket sospeso fino al '.\Carbon\Carbon::parse($data['until'])->isoFormat('D MMM YYYY').'.',
        ]);
    }

    public function defer(Intervention $intervention): JsonResponse
    {
        $me = Auth::user();

        if ($intervention->assigned_user_id !== $me->id && $me->role !== 'admin') {
            return response()->json(['ok' => false, 'message' => 'Solo l\'assegnatario può rimandare il ticket.'], 403);
        }

        if (in_array($intervention->status, ['completed', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'Ticket già chiuso o sospeso.'], 422);
        }

        $until = today()->copy()->addDay();

        $intervention->update([
            'status' => 'in_progress',
            'suspended_until' => $until,
        ]);

        InterventionActivityLogger::log($intervention, InterventionLogActions::DEFERRED, [
            'until' => $until->toDateString(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Ticket rinviato a domani.',
        ]);
    }

    public function edit(Intervention $intervention): View
    {
        $user = Auth::user();
        abort_unless($intervention->canBeEditedBy($user), 403);

        [$quickAreas, $quickDepartments, $quickEquipments, $quickMaintenanceRoles, $quickManutentori]
            = HomeController::quickOpenScope($user);

        return view('manutentore.tickets.edit', [
            'intervention' => $intervention,
            'quickAreas' => $quickAreas,
            'quickDepartments' => $quickDepartments,
            'quickEquipments' => $quickEquipments,
            'quickMaintenanceRoles' => $quickMaintenanceRoles,
            'quickManutentori' => $quickManutentori,
        ]);
    }

    public function update(Request $request, Intervention $intervention): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($intervention->canBeEditedBy($user), 403);

        $userDeptIds = $user->departments()->pluck('departments.id')->all();
        $userAreaIds = $user->assignedAreaIds()->all();
        $isManutentoreCreator = $user->role === 'manutentore';

        $rules = [
            'priority' => 'required|in:high,medium,low,fixed_date',
            'scheduled_date' => 'nullable|date|after_or_equal:today|required_if:priority,fixed_date',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'area_id' => ['nullable', \Illuminate\Validation\Rule::in($userAreaIds)],
            'department_id' => ['nullable', \Illuminate\Validation\Rule::in($userDeptIds)],
            'equipment_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('equipments', 'id')
                    ->where('active', true)
                    ->whereIn('department_id', $userDeptIds ?: [-1]),
            ],
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];

        if ($isManutentoreCreator) {
            $rules['assigned_user_id'] = [
                'required',
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where('role', 'manutentore')
                    ->where('active', true),
            ];
        } else {
            $rules['maintenance_role_id'] = 'required|exists:maintenance_roles,id';
        }

        $request->validate($rules, [
            'maintenance_role_id.required' => 'Seleziona una specializzazione.',
            'assigned_user_id.required' => 'Seleziona un manutentore.',
            'assigned_user_id.exists' => 'Manutentore non valido.',
            'priority.required' => 'Seleziona una priorità.',
            'priority.in' => 'Priorità non valida.',
            'scheduled_date.required_if' => 'Seleziona una data per il ticket a data fissa.',
            'scheduled_date.after_or_equal' => 'La data deve essere oggi o successiva.',
            'area_id.in' => 'Area non disponibile.',
            'department_id.in' => 'Zona non disponibile.',
            'equipment_id.exists' => 'Impianto non disponibile.',
        ]);

        $equipmentId = $request->equipment_id ?: null;
        $areaId = $request->area_id ?: null;
        $deptId = $request->department_id ?: null;
        $equipment = null;

        if ($equipmentId) {
            $equipment = \App\Models\Equipment::with('department')->find($equipmentId);
            if ($equipment && $equipment->department) {
                $deptId = $equipment->department_id;
                $areaId = $equipment->department->area_id;
            }
        }

        $area = $areaId ? Area::find($areaId) : null;
        $department = $deptId ? Department::find($deptId) : null;

        $title = $request->filled('title')
            ? $request->title
            : Intervention::generateFallbackTitle([
                'equipment' => $equipment,
                'area' => $area,
                'department' => $department,
                'description' => $request->description,
            ]);

        $isFixedDate = $request->priority === 'fixed_date';

        $intervention->update([
            'area_id' => $areaId,
            'department_id' => $deptId,
            'equipment_id' => $equipmentId,
            'maintenance_role_id' => $isManutentoreCreator ? null : $request->maintenance_role_id,
            'assigned_user_id' => $isManutentoreCreator ? $request->assigned_user_id : null,
            'title' => $title,
            'description' => $request->description,
            'scheduled_date' => $isFixedDate ? $request->scheduled_date : today(),
            'scheduled_start_time' => $isFixedDate ? ($request->scheduled_start_time ?: null) : null,
            'priority' => $request->priority,
        ]);

        InterventionActivityLogger::log($intervention, InterventionLogActions::UPDATED, [
            'edited_by_creator' => true,
        ]);

        return redirect()
            ->route('m.tickets.index')
            ->with('success', 'Ticket aggiornato.');
    }

    public function destroy(Intervention $intervention): JsonResponse
    {
        $user = Auth::user();

        if (! $intervention->canBeDeletedBy($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'Non puoi eliminare questo ticket.',
            ], 403);
        }

        InterventionActivityLogger::log($intervention, InterventionLogActions::CANCELLED, [
            'reason' => 'deleted_by_creator',
        ]);

        $intervention->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Ticket eliminato.',
            'redirect' => route('m.tickets.index'),
        ]);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $userDeptIds = $user->departments()->pluck('departments.id')->all();
        $userAreaIds = $user->assignedAreaIds()->all();
        $isManutentoreCreator = $user->role === 'manutentore';

        $rules = [
            'priority' => 'required|in:high,medium,low,fixed_date',
            'scheduled_date' => 'nullable|date|after_or_equal:today|required_if:priority,fixed_date',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'area_id' => [$isManutentoreCreator ? 'nullable' : 'required', \Illuminate\Validation\Rule::in($userAreaIds)],
            'department_id' => [$isManutentoreCreator ? 'nullable' : 'required', \Illuminate\Validation\Rule::in($userDeptIds)],
            'equipment_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('equipments', 'id')
                    ->where('active', true)
                    ->whereIn('department_id', $userDeptIds ?: [-1]),
            ],
            'title' => [$isManutentoreCreator ? 'nullable' : 'required', 'string', 'max:255'],
            'description' => 'nullable|string|max:2000',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,zip|max:10240',
        ];

        if ($isManutentoreCreator) {
            // Manutentore creatore → assegnazione diretta a un collega manutentore
            $rules['assigned_user_id'] = [
                'required',
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where('role', 'manutentore')
                    ->where('active', true),
            ];
        } else {
            // Operatore → sceglie la specializzazione (auto-assegnazione via servizio)
            // oppure può indicare direttamente un manutentore (override).
            $rules['maintenance_role_id'] = 'required|exists:maintenance_roles,id';
            $rules['assigned_user_id'] = [
                'nullable',
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where('role', 'manutentore')
                    ->where('active', true),
            ];
        }

        $request->validate($rules, [
            'maintenance_role_id.required' => 'Seleziona una specializzazione.',
            'assigned_user_id.required' => 'Seleziona un manutentore.',
            'assigned_user_id.exists' => 'Manutentore non valido.',
            'priority.required' => 'Seleziona una priorità.',
            'priority.in' => 'Priorità non valida.',
            'scheduled_date.required_if' => 'Seleziona una data per il ticket a data fissa.',
            'scheduled_date.after_or_equal' => 'La data deve essere oggi o successiva.',
            'title.required' => 'Inserisci il titolo del ticket.',
            'area_id.required' => 'Seleziona un\'area.',
            'area_id.in' => 'Area non disponibile.',
            'department_id.required' => 'Seleziona una zona.',
            'department_id.in' => 'Zona non disponibile.',
            'equipment_id.exists' => 'Impianto non disponibile.',
            'files.max' => 'Puoi allegare al massimo 10 file.',
            'files.*.mimes' => 'Sono accettati solo PDF, immagini (JPG/PNG/GIF) e ZIP.',
            'files.*.max' => 'Ogni file deve essere al massimo di 10 MB.',
        ]);

        // L'impianto, se presente, vincola area e zona.
        $equipmentId = $request->equipment_id ?: null;
        $areaId = $request->area_id ?: null;
        $deptId = $request->department_id ?: null;
        $equipment = null;

        if ($equipmentId) {
            $equipment = \App\Models\Equipment::with('department')->find($equipmentId);
            if ($equipment && $equipment->department) {
                $deptId = $equipment->department_id;
                $areaId = $equipment->department->area_id;
            }
        }

        $area = $areaId ? Area::find($areaId) : null;
        $department = $deptId ? Department::find($deptId) : null;

        $title = $request->filled('title')
            ? $request->title
            : Intervention::generateFallbackTitle([
                'equipment' => $equipment,
                'area' => $area,
                'department' => $department,
                'description' => $request->description,
            ]);

        $isFixedDate = $request->priority === 'fixed_date';

        // L'operatore può scegliere direttamente un manutentore: in tal caso
        // bypassa l'auto-assegnazione (l'observer la salta se assigned_user_id è già impostato).
        $operatorPickedAssigneeId = (! $isManutentoreCreator && $request->filled('assigned_user_id'))
            ? (int) $request->assigned_user_id
            : null;

        $intervention = Intervention::create([
            'tipo' => 'ordinario',
            'area_id' => $areaId,
            'department_id' => $deptId,
            'equipment_id' => $equipmentId,
            'maintenance_role_id' => $isManutentoreCreator ? null : $request->maintenance_role_id,
            'assigned_user_id' => $isManutentoreCreator
                ? $request->assigned_user_id
                : $operatorPickedAssigneeId,
            'created_by' => $user->id,
            'title' => $title,
            'description' => $request->description,
            'scheduled_date' => $isFixedDate ? $request->scheduled_date : today(),
            'scheduled_start_time' => $isFixedDate ? ($request->scheduled_start_time ?: null) : null,
            'status' => 'open',
            'priority' => $request->priority,
        ]);

        if ($operatorPickedAssigneeId) {
            $picked = User::find($operatorPickedAssigneeId);
            InterventionActivityLogger::log($intervention, InterventionLogActions::MANUALLY_ASSIGNED, [
                'reason' => "L'operatore «{$user->name}» ha scelto direttamente «{$picked?->name}» in fase di apertura del ticket.",
                'from_user_id' => null,
                'to_user_id' => $operatorPickedAssigneeId,
                'to_user_name' => $picked?->name,
            ]);
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('media', 'public');
                Media::create([
                    'mediable_type' => Intervention::class,
                    'mediable_id' => $intervention->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $referer = $request->headers->get('referer');
        $redirectTo = $referer && str_contains($referer, '/m/tickets')
            ? 'm.tickets.index'
            : 'm.home';

        return redirect()
            ->route($redirectTo)
            ->with('success', 'Ticket aperto.');
    }

    private function authorizeVisibility(Intervention $intervention): void
    {
        $user = Auth::user();

        if ($intervention->assigned_user_id === $user->id) {
            return;
        }

        if ($intervention->created_by === $user->id) {
            return;
        }

        // Anche le collaborazioni "pending" devono poter aprire il modal,
        // altrimenti il destinatario non riesce ad accettare/rifiutare la richiesta.
        $hasCollab = $intervention->collaborations()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                InterventionCollaboration::STATUS_PENDING,
                InterventionCollaboration::STATUS_ACCEPTED,
            ])
            ->exists();
        if ($hasCollab) {
            return;
        }

        $deptIds = $user->departments()->pluck('departments.id')->all();
        if (in_array($intervention->department_id, $deptIds, true)) {
            return;
        }
        if ($intervention->equipment && in_array($intervention->equipment->department_id, $deptIds, true)) {
            return;
        }

        abort(403);
    }

    private function initials(string $name): string
    {
        return mb_strtoupper(
            collect(explode(' ', trim($name)))
                ->filter()
                ->map(fn ($n) => mb_substr($n, 0, 1))
                ->take(2)
                ->implode('')
        );
    }

    private function formatDuration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }
        if ($h > 0) {
            return "{$h}h";
        }

        return "{$m}m";
    }
}
