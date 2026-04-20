<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\InterventionLog;
use Illuminate\Support\Facades\Auth;

class InterventionActivityLogger
{
    public static function log(Intervention $intervention, string $action, array $metadata = [], ?int $userId = null): InterventionLog
    {
        return InterventionLog::create([
            'intervention_id' => $intervention->id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    public static function system(Intervention $intervention, string $action, array $metadata = []): InterventionLog
    {
        return self::log($intervention, $action, $metadata, null);
    }
}
