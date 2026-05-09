<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\Intervention;
use Carbon\Carbon;

/**
 * Costruisce ticket pianificati a partire da un Impianto (Equipment) o un Componente
 * (EquipmentComponent) usando la configurazione di pianificazione salvata sull'entità.
 *
 * È usato in due punti:
 *  - alla creazione dell'Equipment dal form admin (primo ticket del ciclo);
 *  - dall'InterventionObserver alla chiusura di un pianificato per generare il successivo.
 */
class PlannedInterventionFactory
{
    /**
     * Crea il ticket pianificato per l'impianto, se la configurazione è completa.
     * Ritorna l'Intervention creato o null se mancano i dati minimi.
     */
    public function createForEquipment(Equipment $equipment, ?Carbon $scheduledDate = null, ?int $createdBy = null): ?Intervention
    {
        $date = $scheduledDate ?? $equipment->next_maintenance_date;

        if (! $date || ! $equipment->intervention_title) {
            return null;
        }

        return Intervention::create([
            'tipo' => 'pianificazione',
            'equipment_id' => $equipment->id,
            'component_id' => null,
            'maintenance_role_id' => $equipment->assignment_type === 'specializzazione'
                ? $equipment->maintenance_role_id
                : null,
            'assigned_user_id' => $equipment->assignment_type === 'diretto'
                ? $equipment->assigned_user_id
                : null,
            'created_by' => $createdBy,
            'title' => $equipment->intervention_title,
            'description' => $equipment->intervention_description,
            'scheduled_date' => $date->copy(),
            'scheduled_start_time' => '08:00:00',
            'status' => 'planned',
            'priority' => 'low',
        ]);
    }

    /**
     * Crea il ticket pianificato per un componente.
     */
    public function createForComponent(EquipmentComponent $component, ?Carbon $scheduledDate = null, ?int $createdBy = null): ?Intervention
    {
        $date = $scheduledDate ?? $component->next_maintenance_date;

        if (! $date || ! $component->intervention_title) {
            return null;
        }

        return Intervention::create([
            'tipo' => 'pianificazione',
            'equipment_id' => $component->equipment_id,
            'component_id' => $component->id,
            'maintenance_role_id' => $component->assignment_type === 'specializzazione'
                ? $component->maintenance_role_id
                : null,
            'assigned_user_id' => $component->assignment_type === 'diretto'
                ? $component->assigned_user_id
                : null,
            'created_by' => $createdBy,
            'title' => $component->intervention_title,
            'description' => $component->intervention_description,
            'scheduled_date' => $date->copy(),
            'scheduled_start_time' => '08:00:00',
            'status' => 'planned',
            'priority' => 'low',
        ]);
    }
}
