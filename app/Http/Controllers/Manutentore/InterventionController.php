<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Department;
use App\Models\Intervention;
use App\Models\InterventionCollaboration;
use App\Models\InterventionTransfer;
use App\Models\User;
use App\Notifications\CollaborationRequestedNotification;
use App\Notifications\InterventionTransferredNotification;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public const CHIPS = [
        'all'         => 'Tutti',
        'overdue'     => 'Scaduti',
        'today'       => 'Oggi',
        'high'        => 'Alta priorità',
        'low'         => 'Bassa priorità',
        'in_progress' => 'In lavorazione',
        'cancelled'   => 'Sospesi',
        'planned'     => 'Programmati',
        'assigned'    => 'Presi in carico',
    ];

    public function index(Request $request): View
    {
        $user    = Auth::user();
        $deptIds = $user->departments()->pluck('departments.id');
        $filter  = $request->get('filter', 'all');
        $q       = trim((string) $request->get('q', ''));

        if (!array_key_exists($filter, self::CHIPS)) {
            $filter = 'all';
        }

        $query = Intervention::with([
                'equipment.department.area',
                'area',
                'department',
                'maintenanceRole',
                'assignedUser',
                'collaborations' => fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->where('status', InterventionCollaboration::STATUS_ACCEPTED),
                'reports' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->where(function ($q) use ($user, $deptIds) {
                $q->where('assigned_user_id', $user->id);

                $q->orWhereHas('collaborations', fn ($c) => $c
                    ->where('user_id', $user->id)
                    ->where('status', InterventionCollaboration::STATUS_ACCEPTED));

                if ($deptIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($deptIds) {
                        $q2->whereIn('department_id', $deptIds)
                            ->orWhereHas('equipment', fn ($eq) => $eq->whereIn('department_id', $deptIds));
                    });
                }
            });

        switch ($filter) {
            case 'today':
                $query->where('tipo', 'pianificazione')
                    ->whereDate('scheduled_date', today());
                break;

            case 'high':
                $query->whereIn('priority', ['urgent', 'high'])
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;

            case 'low':
                $query->whereIn('priority', ['medium', 'low', 'fixed_date'])
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;

            case 'in_progress':
                $query->where('status', 'in_progress');
                break;

            case 'cancelled':
                $query->where('status', 'cancelled');
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
            $like = '%' . $q . '%';
            $query->where(function ($qq) use ($like) {
                $qq->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('area',            fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('department',      fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('equipment',       fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('assignedUser',    fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('maintenanceRole', fn ($s) => $s->where('name', 'like', $like));
            });
        }

        $interventions = $query
            ->orderByRaw("FIELD(status, 'in_progress', 'open', 'planned', 'completed', 'cancelled')")
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low', 'fixed_date')")
            ->orderBy('created_at', 'desc')
            ->get();

        if ($filter === 'overdue') {
            $interventions = $interventions->filter(function ($i) {
                if ($i->tipo === 'pianificazione'
                    && $i->scheduled_date
                    && $i->scheduled_date->isPast()
                    && !in_array($i->status, ['completed', 'cancelled'])) {
                    return true;
                }
                return $i->is_overdue;
            })->values();
        }

        $quickAreas = Area::where('active', true)->orderBy('name')->get();
        $quickDepartments = Department::where('active', true)->orderBy('name')->get(['id', 'name', 'area_id']);

        return view('manutentore.tickets.index', [
            'interventions'    => $interventions,
            'filter'           => $filter,
            'q'                => $q,
            'chips'            => self::CHIPS,
            'quickAreas'       => $quickAreas,
            'quickDepartments' => $quickDepartments,
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
        ]);

        $me = Auth::user();
        $deadline = $intervention->deadline;
        $isOverdue = $intervention->is_overdue
            || ($intervention->tipo === 'pianificazione'
                && $intervention->scheduled_date
                && $intervention->scheduled_date->isPast()
                && !in_array($intervention->status, ['completed', 'cancelled']));

        $mine = $intervention->assigned_user_id === $me->id;
        $unassigned = is_null($intervention->assigned_user_id);
        $iAmAcceptedCollaborator = $intervention->collaborations
            ->where('user_id', $me->id)
            ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->isNotEmpty();

        $pendingCollabForMe = $intervention->collaborations
            ->firstWhere(fn ($c) => $c->user_id === $me->id && $c->status === InterventionCollaboration::STATUS_PENDING);

        $myReport = $intervention->reports->firstWhere('user_id', $me->id);
        $hasMyReport = (bool) $myReport;

        // Chi deve ancora scrivere il rapportino: assegnatario + collaboratori accettati
        $requiredReporterIds = collect([$intervention->assigned_user_id])
            ->merge($intervention->collaborations
                ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
                ->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        $reportsByUser = $intervention->reports->keyBy('user_id');

        $reportStatuses = $requiredReporterIds->map(function ($userId) use ($reportsByUser, $intervention) {
            $user = $userId === $intervention->assigned_user_id
                ? $intervention->assignedUser
                : $intervention->collaborations->firstWhere('user_id', $userId)?->user;
            $report = $reportsByUser->get($userId);
            return [
                'user_id'   => $userId,
                'name'      => $user?->name,
                'initials'  => $this->initials($user?->name ?? ''),
                'role'      => $userId === $intervention->assigned_user_id ? 'Assegnatario' : 'Collaboratore',
                'has_report'=> (bool) $report,
                'status'    => $report?->status,
            ];
        })->values();

        $area       = $intervention->area ?? $intervention->equipment?->department?->area;
        $department = $intervention->department ?? $intervention->equipment?->department;

        $transfers = $intervention->transfers->map(fn ($t) => [
            'id'              => $t->id,
            'event'           => 'transfer',
            'from_user_name'  => $t->fromUser?->name ?? '—',
            'to_user_name'    => $t->toUser?->name,
            'initiated_by'    => $t->initiatedBy?->name,
            'reason'          => $t->reason,
            'at'              => $t->transferred_at?->isoFormat('D MMM YYYY · HH:mm'),
            'at_sort'         => $t->transferred_at?->timestamp,
        ]);

        $collabEvents = $intervention->collaborations->map(fn ($c) => [
            'id'              => $c->id,
            'event'           => 'collaboration_' . $c->status,
            'user_name'       => $c->user?->name,
            'requested_by'    => $c->requestedBy?->name,
            'message'         => $c->message,
            'status'          => $c->status,
            'at'              => ($c->responded_at ?? $c->requested_at ?? $c->created_at)?->isoFormat('D MMM YYYY · HH:mm'),
            'at_sort'         => ($c->responded_at ?? $c->requested_at ?? $c->created_at)?->timestamp,
        ]);

        $history = $transfers->concat($collabEvents)->sortByDesc('at_sort')->values();

        $collaborators = $intervention->collaborations
            ->whereIn('status', [InterventionCollaboration::STATUS_ACCEPTED, InterventionCollaboration::STATUS_PENDING])
            ->map(fn ($c) => [
                'id'       => $c->id,
                'user_id'  => $c->user_id,
                'name'     => $c->user?->name,
                'status'   => $c->status,
                'initials' => $this->initials($c->user?->name ?? ''),
            ])
            ->values();

        return response()->json([
            'id'          => $intervention->id,
            'code'        => '#' . $intervention->id,
            'title'       => $intervention->title,
            'description' => $intervention->description,
            'tipo'        => $intervention->tipo,
            'tipo_label'  => $intervention->tipo === 'pianificazione' ? 'Pianificato' : 'Libero',
            'status'      => $intervention->status,
            'status_label' => [
                'open'        => 'Aperto',
                'planned'     => 'Pianificato',
                'in_progress' => 'In carico',
                'completed'   => 'Completato',
                'cancelled'   => 'Sospeso',
            ][$intervention->status] ?? $intervention->status,
            'priority'       => $intervention->priority,
            'priority_label' => [
                'urgent'     => 'Urgente',
                'high'       => 'Alta',
                'medium'     => 'Media',
                'low'        => 'Bassa',
                'fixed_date' => 'Data fissa',
            ][$intervention->priority] ?? $intervention->priority,
            'created_at'         => $intervention->created_at?->isoFormat('D MMM YYYY · HH:mm'),
            'scheduled_date'     => $intervention->scheduled_date?->isoFormat('dddd D MMM YYYY'),
            'scheduled_start'    => $intervention->scheduled_start_time,
            'duration_minutes'   => $intervention->estimated_duration_minutes,
            'area'               => $area?->name,
            'department'         => $department?->name,
            'equipment'          => $intervention->equipment?->name,
            'maintenance_role'   => $intervention->maintenanceRole?->name,
            'assigned_user'      => $intervention->assignedUser
                ? array_merge($intervention->assignedUser->only(['id', 'name']),
                    ['initials' => $this->initials($intervention->assignedUser->name)])
                : null,
            'mine'               => $mine,
            'unassigned'         => $unassigned,
            'is_overdue'         => $isOverdue,
            'overdue_since'      => $isOverdue && $deadline
                ? $deadline->diffForHumans(['parts' => 2, 'syntax' => CarbonInterface::DIFF_ABSOLUTE])
                : null,
            'deadline_at'        => $deadline?->isoFormat('D MMM · HH:mm'),
            'notes'              => $intervention->notes,
            'collaborators'      => $collaborators,
            'report_statuses'    => $reportStatuses,
            'has_my_report'      => $hasMyReport,
            'my_report_id'       => $myReport?->id,
            'history'            => $history,
            'pending_request'    => $pendingCollabForMe ? [
                'id'              => $pendingCollabForMe->id,
                'requested_by'    => $pendingCollabForMe->requestedBy?->name,
                'message'         => $pendingCollabForMe->message,
                'respond_url'     => route('m.collaborations.respond', $pendingCollabForMe->id),
            ] : null,
            'urls' => [
                'take_charge'       => route('interventions.take-charge', $intervention),
                'report_create'     => route('interventions.reports.create', $intervention),
                'details'           => route('interventions.show', $intervention),
                'transfer'          => route('m.interventions.transfer', $intervention),
                'collaboration'     => route('m.interventions.collaboration', $intervention),
                'candidates'        => route('m.interventions.candidates', $intervention),
            ],
            'actions' => [
                'can_take_charge'    => $unassigned && in_array($intervention->status, ['open', 'planned']),
                'can_create_report'  => ($mine || $iAmAcceptedCollaborator)
                                        && $intervention->status === 'in_progress'
                                        && !$hasMyReport,
                'can_edit_report'    => $hasMyReport && $myReport?->status !== 'chiuso',
                'can_transfer'       => $mine && !in_array($intervention->status, ['completed', 'cancelled']),
                'can_collaborate'    => ($mine || $iAmAcceptedCollaborator) && !in_array($intervention->status, ['completed', 'cancelled']),
            ],
        ]);
    }

    public function candidatesJson(Request $request, Intervention $intervention): JsonResponse
    {
        $this->authorizeVisibility($intervention);
        $purpose = $request->get('purpose', 'transfer');

        $query = User::whereIn('role', ['manutentore', 'operator'])
            ->where('id', '!=', Auth::id());

        if ($intervention->maintenance_role_id) {
            $query->whereHas('maintenanceRoles', fn ($q) =>
                $q->where('maintenance_roles.id', $intervention->maintenance_role_id)
            );
        }

        $deptId = $intervention->department_id ?? $intervention->equipment?->department_id;
        if ($deptId) {
            $query->whereHas('departments', fn ($q) =>
                $q->where('departments.id', $deptId)
            );
        }

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
        if ($purpose === 'collaboration') {
            $users = $users->filter(fn ($u) => $u->isOnShift())->values();
        }

        $candidates = $users->map(fn ($u) => [
            'id'       => $u->id,
            'name'     => $u->name,
            'role'     => ucfirst($u->role),
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
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        if ((int) $data['to_user_id'] === (int) $intervention->assigned_user_id) {
            return response()->json(['ok' => false, 'message' => 'Il destinatario coincide con l\'assegnatario attuale.'], 422);
        }

        $toUser = User::findOrFail($data['to_user_id']);

        InterventionTransfer::create([
            'intervention_id'      => $intervention->id,
            'from_user_id'         => $intervention->assigned_user_id,
            'to_user_id'           => $toUser->id,
            'initiated_by_user_id' => $me->id,
            'reason'               => $data['reason'] ?? null,
            'transferred_at'       => now(),
        ]);

        $intervention->update([
            'assigned_user_id' => $toUser->id,
            'status' => in_array($intervention->status, ['open', 'planned']) ? 'in_progress' : $intervention->status,
        ]);

        $toUser->notify(new InterventionTransferredNotification($intervention->fresh(), $me, $data['reason'] ?? null));

        return response()->json([
            'ok'      => true,
            'message' => 'Ticket trasferito a ' . $toUser->name . '.',
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

        if (!$isAssignee && !$isCollaborator && $me->role !== 'admin') {
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
            'intervention_id'      => $intervention->id,
            'user_id'              => $data['user_id'],
            'requested_by_user_id' => $me->id,
            'status'               => InterventionCollaboration::STATUS_PENDING,
            'message'              => $data['message'] ?? null,
            'requested_at'         => now(),
        ]);

        $target = User::find($data['user_id']);
        $target->notify(new CollaborationRequestedNotification($collab));

        return response()->json([
            'ok'      => true,
            'message' => 'Richiesta inviata a ' . $target->name . '.',
        ]);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $request->validate([
            'area_id'       => 'required|exists:areas,id',
            'department_id' => 'required|exists:departments,id',
            'description'   => 'nullable|string',
        ], [
            'area_id.required'       => 'Seleziona un\'area.',
            'department_id.required' => 'Seleziona una zona.',
        ]);

        $area       = Area::find($request->area_id);
        $department = Department::find($request->department_id);

        Intervention::create([
            'tipo'             => 'ordinario',
            'area_id'          => $request->area_id,
            'department_id'    => $request->department_id,
            'assigned_user_id' => Auth::id(),
            'title'            => 'Intervento - ' . $area->name . ' / ' . $department->name,
            'description'      => $request->description,
            'scheduled_date'   => today(),
            'status'           => 'in_progress',
            'priority'         => 'medium',
        ]);

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

        $isAcceptedCollaborator = $intervention->collaborations()
            ->where('user_id', $user->id)
            ->where('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->exists();
        if ($isAcceptedCollaborator) {
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
}
