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
        'next_maintenance_date',
    ];

    protected $casts = [
        'next_maintenance_date' => 'date',
        'frequency_days' => 'integer',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
