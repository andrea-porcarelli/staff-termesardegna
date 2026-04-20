<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE interventions SET priority = 'high' WHERE priority = 'urgent'");
        DB::statement("UPDATE interventions SET priority = 'low' WHERE priority = 'medium'");
        DB::statement("ALTER TABLE interventions MODIFY COLUMN priority ENUM('low','high','fixed_date') NOT NULL DEFAULT 'low'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE interventions MODIFY COLUMN priority ENUM('low','medium','high','urgent','fixed_date') NOT NULL DEFAULT 'medium'");
    }
};
