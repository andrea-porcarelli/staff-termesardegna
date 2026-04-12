<?php

namespace App\Observers;

use App\Models\Intervention;
use App\Services\AutoAssignmentService;

class InterventionObserver
{
    /**
     * Risultato dell'ultima auto-assegnazione.
     * Letto dal controller subito dopo Intervention::create().
     */
    public static array $lastAssignmentResult = ['status' => 'skipped', 'user' => null, 'shift_start' => null];

    public function created(Intervention $intervention): void
    {
        static::$lastAssignmentResult = (new AutoAssignmentService())->assign($intervention);
    }
}
