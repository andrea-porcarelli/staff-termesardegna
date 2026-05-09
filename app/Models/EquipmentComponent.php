<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentComponent extends Model
{
    protected $fillable = [
        'equipment_id',
        'name',
        'description',
        'maintenance_type',
        'frequency_days',
        'last_maintenance_date',
        'next_maintenance_date',
        'assignment_type',
        'maintenance_role_id',
        'assigned_user_id',
        'intervention_title',
        'intervention_description',
    ];

    protected $casts = [
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'frequency_days' => 'integer',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
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
