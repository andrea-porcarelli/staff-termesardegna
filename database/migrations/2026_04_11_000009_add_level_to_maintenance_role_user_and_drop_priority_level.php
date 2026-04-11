<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_role_user', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(1)->after('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('priority_level');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_role_user', function (Blueprint $table) {
            $table->dropColumn('level');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority_level')->nullable()->after('role');
        });
    }
};
