<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE interventions MODIFY COLUMN status ENUM('open', 'planned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'planned'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE interventions MODIFY COLUMN status ENUM('planned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'planned'");
    }
};
