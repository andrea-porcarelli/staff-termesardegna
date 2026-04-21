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
            'low' => $base->copy()->addDays(7),
            default => null,
        };
    }

    /**
     * Verifica se l'intervento è scaduto.
     */
    public function getIsOverdueAttribute(): bool
    {
        $deadline = $this->deadline;
        if (! $deadline) {
            return false;
        }
        if (in_array($this->status, ['completed', 'cancelled'])) {
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
