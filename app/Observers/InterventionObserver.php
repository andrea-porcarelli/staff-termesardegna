<?php

namespace App\Observers;

use App\Models\Intervention;
use App\Services\AutoAssignmentService;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;

class InterventionObserver
{
    /**
     * Risultato dell'ultima auto-assegnazione.
     * Letto dal controller subito dopo Intervention::create().
     */
    public static array $lastAssignmentResult = ['status' => 'skipped', 'user' => null, 'shift_start' => null];

    public function created(Intervention $intervention): void
    {
        InterventionActivityLogger::log($intervention, InterventionLogActions::CREATED, [
            'tipo' => $intervention->tipo,
            'priority' => $intervention->priority,
            'status' => $intervention->status,
        ]);

        static::$lastAssignmentResult = (new AutoAssignmentService)->assign($intervention);
    }
}
