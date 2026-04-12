<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleSlot extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'type',
        'is_recurring',
        'group_id',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
        'day_of_week'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
