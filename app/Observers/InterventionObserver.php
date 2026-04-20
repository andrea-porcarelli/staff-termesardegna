<?php

namespace App\Observers;

use App\Models\Intervention;
use App\Notifications\NuovoTicketAssegnatoNotification;
use App\Services\AutoAssignmentService;
use App\Services\InterventionActivityLogger;
use App\Support\InterventionLogActions;

class InterventionObserver
{
    /**
     * Priorità per cui inviare la push di nuovo ticket assegnato.
     */
    private const PUSH_PRIORITIES = ['high', 'fixed_date'];

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

        $this->notifyAssigneeIfNeeded($intervention->fresh());
    }

    private function notifyAssigneeIfNeeded(?Intervention $intervention): void
    {
        if (! $intervention || ! $intervention->assigned_user_id) {
            return;
        }
        if (! in_array($intervention->priority, self::PUSH_PRIORITIES, true)) {
            return;
        }

        $intervention->assignedUser?->notify(new NuovoTicketAssegnatoNotification($intervention));
    }
}
