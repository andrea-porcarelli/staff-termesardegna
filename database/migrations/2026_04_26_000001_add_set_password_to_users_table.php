<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('set_password')->default(false)->after('active');
        });

        // Backfill: utenti già esistenti hanno una password, quindi flag = true
        // così non vengono bloccati. I nuovi operator/manutentore partiranno
        // con set_password=false e potranno impostare la password al primo accesso.
        DB::table('users')->update(['set_password' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('set_password');
        });
    }
};
