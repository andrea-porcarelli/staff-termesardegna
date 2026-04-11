<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority_level')->nullable()->change()
                  ->comment('Livello minimo di priorità (1=massima, 5=minima) degli interventi visibili al manutentore');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent'])->nullable()->change();
        });
    }
};
