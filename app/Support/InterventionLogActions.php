<?php

namespace App\Support;

class InterventionLogActions
{
    public const CREATED = 'created';

    public const AUTO_ASSIGNED = 'auto_assigned';

    public const MANUALLY_ASSIGNED = 'manually_assigned';

    public const TAKEN_IN_CHARGE = 'taken_in_charge';

    public const UPDATED = 'updated';

    public const TRANSFERRED = 'transferred';

    public const COLLABORATION_REQUESTED = 'collaboration_requested';

    public const COLLABORATION_ACCEPTED = 'collaboration_accepted';

    public const COLLABORATION_DECLINED = 'collaboration_declined';

    public const REPORT_CREATED = 'report_created';

    public const REPORT_UPDATED = 'report_updated';

    public const REPORT_CLOSED = 'report_closed';

    public const REPORT_DELETED = 'report_deleted';

    public const COMPLETED = 'completed';

    public const SUSPENDED = 'suspended';

    public const DEFERRED = 'deferred';

    public const CANCELLED = 'cancelled';

    public const LABELS = [
        self::CREATED => 'Creato',
        self::AUTO_ASSIGNED => 'Auto-assegnato',
        self::MANUALLY_ASSIGNED => 'Assegnato manualmente',
        self::TAKEN_IN_CHARGE => 'Preso in carico',
        self::UPDATED => 'Modificato',
        self::TRANSFERRED => 'Trasferito',
        self::COLLABORATION_REQUESTED => 'Collaborazione richiesta',
        self::COLLABORATION_ACCEPTED => 'Collaborazione accettata',
        self::COLLABORATION_DECLINED => 'Collaborazione rifiutata',
        self::REPORT_CREATED => 'Rapportino creato',
        self::REPORT_UPDATED => 'Rapportino aggiornato',
        self::REPORT_CLOSED => 'Rapportino chiuso',
        self::REPORT_DELETED => 'Rapportino eliminato',
        self::COMPLETED => 'Intervento completato',
        self::SUSPENDED => 'Sospeso',
        self::DEFERRED => 'Rinviato',
        self::CANCELLED => 'Annullato',
    ];

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? $action;
    }

    public static function all(): array
    {
        return self::LABELS;
    }
}
