<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Intervention extends Model
{
    protected $fillable = [
        'tipo',
        'equipment_id',
        'component_id',
        'maintenance_role_id',
        'area_id',
        'department_id',
        'assigned_user_id',
        'created_by',
        'title',
        'description',
        'scheduled_date',
        'scheduled_start_time',
        'estimated_duration_minutes',
        'status',
        'priority',
        'notes',
        'completed_at',
        'suspended_until',
        'preso_in_carico_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'suspended_until' => 'date',
        'preso_in_carico_at' => 'datetime',
    ];

    public function getIsPresoInCaricoAttribute(): bool
    {
        return $this->preso_in_carico_at !== null;
    }

    /**
     * Regole di modifica:
     * - admin: sempre;
     * - creatore (qualunque ruolo): finché il ticket non è stato preso in carico;
     * - operator: tutti i ticket che ricadono in una sua area o zona assegnata.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        if ($this->created_by === $user->id && $this->preso_in_carico_at === null) {
            return true;
        }
        if ($user->role === 'operator') {
            $userDeptIds = $user->departments()->pluck('departments.id')->all();
            $userAreaIds = $user->assignedAreaIds()->all();
            if ($this->department_id && in_array($this->department_id, $userDeptIds, true)) {
                return true;
            }
            if ($this->area_id && in_array($this->area_id, $userAreaIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Regole di cancellazione (più restrittive della modifica): admin sempre,
     * oppure creatore finché il ticket non è stato preso in carico.
     */
    public function canBeDeletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }

        return $this->created_by === $user->id && $this->preso_in_carico_at === null;
    }

    /**
     * Titolo di fallback quando chi crea il ticket non ne fornisce uno.
     * Context accetta istanze di Equipment/Area/Department e/o string 'description'.
     */
    public static function generateFallbackTitle(array $context): string
    {
        $equipment = $context['equipment'] ?? null;
        $area = $context['area'] ?? null;
        $department = $context['department'] ?? null;
        $description = $context['description'] ?? null;

        return match (true) {
            (bool) $equipment => 'Ticket - '.$equipment->name,
            $area && $department => 'Ticket - '.$area->name.' / '.$department->name,
            (bool) $area => 'Ticket - '.$area->name,
            ! empty($description) => 'Ticket - '.mb_strimwidth((string) $description, 0, 60, '…'),
            default => 'Ticket',
        };
    }

    /**
     * Calcola la data di scadenza in base alla priorità.
     * low = +7gg, high = +24h, fixed_date = scheduled_date
     */
    public function getDeadlineAttribute(): ?\Carbon\Carbon
    {
        if ($this->priority === 'fixed_date') {
            return $this->scheduled_date;
        }

        $base = $this->created_at;
        if (! $base) {
            return null;
        }

        return match ($this->priority) {
            'high' => $base->copy()->addHours(24),
            'medium' => $base->copy()->addHours(48),
            'low' => $base->copy()->addDays(7),
            default => null,
        };
    }

    /**
     * Datetime completo di pianificazione (data + ora inizio, default 08:00).
     */
    public function getScheduledAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->scheduled_date) {
            return null;
        }

        return $this->scheduled_date
            ->copy()
            ->setTimeFromTimeString($this->scheduled_start_time ?? '08:00:00');
    }

    /**
     * Verifica se l'intervento è scaduto.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        if ($this->tipo === 'pianificazione' && $this->scheduled_at) {
            return now()->greaterThan($this->scheduled_at);
        }

        $deadline = $this->deadline;
        if (! $deadline) {
            return false;
        }

        return now()->greaterThan($deadline);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(EquipmentComponent::class, 'component_id');
    }

    public function maintenanceRole(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRole::class, 'maintenance_role_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Rapportini (di qualsiasi utente) che spostano la data di esecuzione
     * del ticket nel futuro tramite next_work_date.
     */
    public function activeReschedules(): HasMany
    {
        return $this->hasMany(Report::class)
            ->where('is_final', false)
            ->whereNotNull('next_work_date')
            ->whereDate('next_work_date', '>', now()->toDateString());
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(InterventionTransfer::class)->orderByDesc('transferred_at');
    }

    public function collaborations(): HasMany
    {
        return $this->hasMany(InterventionCollaboration::class)->orderByDesc('created_at');
    }

    /**
     * Collaborazioni visibili sulla card (pending + accepted), per mostrare
     * circolini iniziali e badge "richiesta inviata".
     */
    public function activeCollaborations(): HasMany
    {
        return $this->hasMany(InterventionCollaboration::class)
            ->whereIn('status', [
                InterventionCollaboration::STATUS_PENDING,
                InterventionCollaboration::STATUS_ACCEPTED,
            ]);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InterventionLog::class)->latest('created_at');
    }

    public function collaborators(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'intervention_collaborations')
            ->withPivot(['status', 'requested_by_user_id', 'message', 'requested_at', 'responded_at'])
            ->wherePivot('status', InterventionCollaboration::STATUS_ACCEPTED)
            ->withTimestamps();
    }
}
