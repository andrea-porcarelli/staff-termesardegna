<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->date('suspended_until')->nullable()->after('completed_at');
            $table->index('suspended_until');
        });
    }

    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropIndex(['suspended_until']);
            $table->dropColumn('suspended_until');
        });
    }
};
