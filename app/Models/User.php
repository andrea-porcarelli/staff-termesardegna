<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Impersonate, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'set_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'set_password' => 'boolean',
        ];
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class, 'assigned_user_id');
    }

    /**
     * Zone assegnate all'utente (many-to-many)
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)->withTimestamps();
    }

    public function maintenanceRoles(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceRole::class)->withPivot('level')->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function workScheduleSlots(): HasMany
    {
        return $this->hasMany(WorkScheduleSlot::class)
            ->orderBy('is_recurring', 'desc')
            ->orderBy('day_of_week')
            ->orderBy('date')
            ->orderBy('start_time');
    }

    public function oneSignalSubscriptions(): HasMany
    {
        return $this->hasMany(UserOneSignalSubscription::class);
    }

    public function routeNotificationForOneSignal(): array
    {
        return $this->oneSignalSubscriptions()->pluck('player_id')->all();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\SetPasswordLinkNotification($token));
    }

    /**
     * ID delle aree effettivamente assegnate (via departments o areas dirette).
     */
    public function assignedAreaIds(): \Illuminate\Support\Collection
    {
        $this->loadMissing(['departments', 'areas']);

        return $this->departments->pluck('area_id')
            ->merge($this->areas->pluck('id'))
            ->unique()
            ->filter()
            ->values();
    }

    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(InterventionCollaboration::class)
            ->where('status', InterventionCollaboration::STATUS_PENDING);
    }

    /**
     * Verifica se l'utente è in turno al momento $at (default now).
     * Segue le stesse regole dei WorkScheduleSlot: esclude ferie/riposi,
     * supporta slot ricorrenti (day_of_week) e one-off (date).
     */
    public function isOnShift(?Carbon $at = null): bool
    {
        $at = $at ?? now();
        $dateStr = $at->toDateString();

        $slots = $this->relationLoaded('workScheduleSlots')
            ? $this->workScheduleSlots
            : $this->workScheduleSlots()->get();

        foreach ($slots as $slot) {
            if (in_array($slot->type, ['ferie', 'riposi'], true)
                && $slot->date?->toDateString() === $dateStr) {
                return false;
            }
        }

        foreach ($slots as $slot) {
            if ($slot->type !== 'lavorativo') {
                continue;
            }
            if (! $slot->start_time || ! $slot->end_time) {
                continue;
            }

            if ($slot->is_recurring) {
                if ((int) $slot->day_of_week !== $at->dayOfWeek) {
                    continue;
                }
                $start = $at->copy()->setTimeFromTimeString($slot->start_time);
                $end = $at->copy()->setTimeFromTimeString($slot->end_time);
                if ($at->between($start, $end)) {
                    return true;
                }
            } else {
                if ($slot->date?->toDateString() !== $dateStr) {
                    continue;
                }
                $start = Carbon::parse($dateStr.' '.$slot->start_time);
                $end = Carbon::parse($dateStr.' '.$slot->end_time);
                if ($at->between($start, $end)) {
                    return true;
                }
            }
        }

        return false;
    }
}
