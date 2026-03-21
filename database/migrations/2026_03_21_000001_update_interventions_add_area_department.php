<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null')->after('equipment_id');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('area_id');
            $table->foreignId('equipment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['area_id', 'department_id']);
            $table->foreignId('equipment_id')->nullable(false)->change();
        });
    }
};
