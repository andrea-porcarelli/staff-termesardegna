<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'manufacturer',
        'model',
        'serial_number',
        'installation_date',
        'maintenance_frequency_days',
        'last_maintenance_date',
        'next_maintenance_date',
        'assignment_type',
        'maintenance_role_id',
        'assigned_user_id',
        'intervention_title',
        'intervention_description',
        'active',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(EquipmentComponent::class);
    }

    public function maintenanceRole(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRole::class, 'maintenance_role_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
