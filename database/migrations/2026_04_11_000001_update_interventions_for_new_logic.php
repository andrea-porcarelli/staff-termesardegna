<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->unsignedBigInteger('component_id')->nullable()->after('equipment_id');
            $table->foreign('component_id')->references('id')->on('equipment_components')->nullOnDelete();

            $table->unsignedBigInteger('maintenance_role_id')->nullable()->after('component_id');
            $table->foreign('maintenance_role_id')->references('id')->on('maintenance_roles')->nullOnDelete();

            $table->unsignedBigInteger('assigned_user_id')->nullable()->change();
        });

        // Aggiorna priority: critical -> urgent, aggiunge urgent e fixed_date
        DB::statement("UPDATE interventions SET priority = 'urgent' WHERE priority = 'critical'");
        DB::statement("ALTER TABLE interventions MODIFY COLUMN priority ENUM('low','medium','high','urgent','fixed_date') NOT NULL DEFAULT 'medium'");
    }

    public function down(): void
    {
        DB::statement("UPDATE interventions SET priority = 'medium' WHERE priority IN ('urgent', 'fixed_date')");
        DB::statement("ALTER TABLE interventions MODIFY COLUMN priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium'");

        Schema::table('interventions', function (Blueprint $table) {
            $table->dropForeign(['maintenance_role_id']);
            $table->dropColumn('maintenance_role_id');

            $table->dropForeign(['component_id']);
            $table->dropColumn('component_id');

            $table->unsignedBigInteger('assigned_user_id')->nullable(false)->change();
        });
    }
};
