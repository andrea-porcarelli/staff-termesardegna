<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->enum('assignment_type', ['specializzazione', 'diretto'])
                ->nullable()
                ->after('next_maintenance_date');
            $table->foreignId('maintenance_role_id')
                ->nullable()
                ->after('assignment_type')
                ->constrained('maintenance_roles')
                ->nullOnDelete();
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('maintenance_role_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('intervention_title')->nullable()->after('assigned_user_id');
            $table->text('intervention_description')->nullable()->after('intervention_title');
        });

        Schema::table('equipment_components', function (Blueprint $table) {
            $table->date('last_maintenance_date')->nullable()->after('frequency_days');
            $table->enum('assignment_type', ['specializzazione', 'diretto'])
                ->nullable()
                ->after('next_maintenance_date');
            $table->foreignId('maintenance_role_id')
                ->nullable()
                ->after('assignment_type')
                ->constrained('maintenance_roles')
                ->nullOnDelete();
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('maintenance_role_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('intervention_title')->nullable()->after('assigned_user_id');
            $table->text('intervention_description')->nullable()->after('intervention_title');
        });
    }

    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropForeign(['maintenance_role_id']);
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn([
                'assignment_type',
                'maintenance_role_id',
                'assigned_user_id',
                'intervention_title',
                'intervention_description',
            ]);
        });

        Schema::table('equipment_components', function (Blueprint $table) {
            $table->dropForeign(['maintenance_role_id']);
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn([
                'last_maintenance_date',
                'assignment_type',
                'maintenance_role_id',
                'assigned_user_id',
                'intervention_title',
                'intervention_description',
            ]);
        });
    }
};
