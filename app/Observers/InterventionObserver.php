<?php

namespace App\Observers;

use App\Models\Intervention;
use App\Notifications\NuovoTicketAssegnatoNotification;
use App\Services\AutoAssignmentService;
use App\Services\InterventionActivityLogger;
use App\Services\PlannedInterventionFactory;
use App\Support\InterventionLogActions;
use Carbon\Carbon;

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

    /**
     * Quando un ticket pianificato viene completato, genera il successivo
     * usando la frequenza configurata su Equipment/EquipmentComponent.
     */
    public function updated(Intervention $intervention): void
    {
        if (! $intervention->wasChanged('status')) {
            return;
        }
        if ($intervention->status !== 'completed') {
            return;
        }
        if ($intervention->getOriginal('status') === 'completed') {
            return;
        }
        if ($intervention->tipo !== 'pianificazione') {
            return;
        }

        $this->generateNextCycle($intervention);
    }

    /**
     * Calcola la data di completamento, aggiorna last/next sull'entità sorgente
     * (Equipment o EquipmentComponent) e crea il prossimo ticket pianificato.
     */
    private function generateNextCycle(Intervention $intervention): void
    {
        $factory = app(PlannedInterventionFactory::class);
        $completionDate = ($intervention->completed_at ?? now())->copy()->startOfDay();

        if ($intervention->component_id) {
            $component = $intervention->component()->first();
            if (! $component || ! $component->frequency_days || ! $component->intervention_title) {
                return;
            }
            $nextDate = $completionDate->copy()->addDays((int) $component->frequency_days);
            $component->update([
                'last_maintenance_date' => $completionDate,
                'next_maintenance_date' => $nextDate,
            ]);
            $factory->createForComponent($component->fresh(), $nextDate);

            return;
        }

        if ($intervention->equipment_id) {
            $equipment = $intervention->equipment()->first();
            if (! $equipment || ! $equipment->maintenance_frequency_days || ! $equipment->intervention_title) {
                return;
            }
            $nextDate = $completionDate->copy()->addDays((int) $equipment->maintenance_frequency_days);
            $equipment->update([
                'last_maintenance_date' => $completionDate,
                'next_maintenance_date' => $nextDate,
            ]);
            $factory->createForEquipment($equipment->fresh(), $nextDate);
        }
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
