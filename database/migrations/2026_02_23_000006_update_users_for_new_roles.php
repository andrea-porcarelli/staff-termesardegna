<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing 'supervisor' to 'operator'
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'operator']);

        // Update the ENUM to include 'manutentore' and remove 'supervisor'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'manutentore') NOT NULL DEFAULT 'operator'");

        // Add maintenance_role_id foreign key
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('maintenance_role_id')
                ->nullable()
                ->after('role')
                ->constrained('maintenance_roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['maintenance_role_id']);
            $table->dropColumn('maintenance_role_id');
        });

        DB::table('users')->where('role', 'manutentore')->update(['role' => 'operator']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'supervisor', 'operator') NOT NULL DEFAULT 'operator'");
    }
};
