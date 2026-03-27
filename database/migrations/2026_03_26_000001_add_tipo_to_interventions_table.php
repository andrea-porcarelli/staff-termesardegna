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
            $table->enum('tipo', ['pianificazione', 'ordinario'])->default('pianificazione')->after('id');
            $table->date('scheduled_date')->nullable()->change();
        });

        // Imposta 'ordinario' per gli interventi già esistenti senza equipment
        DB::table('interventions')
            ->whereNull('equipment_id')
            ->update(['tipo' => 'ordinario']);
    }

    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropColumn('tipo');
            $table->date('scheduled_date')->nullable(false)->change();
        });
    }
};
