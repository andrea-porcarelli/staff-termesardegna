<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE work_schedule_slots MODIFY COLUMN type ENUM('lavorativo', 'ferie', 'riposi', 'pausa_pranzo') NOT NULL DEFAULT 'lavorativo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE work_schedule_slots MODIFY COLUMN type ENUM('lavorativo', 'ferie', 'riposi') NOT NULL DEFAULT 'lavorativo'");
    }
};
