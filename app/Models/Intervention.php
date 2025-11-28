<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Intervention extends Model
{
    protected $fillable = [
        'equipment_id',
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
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
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
}
