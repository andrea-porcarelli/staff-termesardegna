<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_role_user', function (Blueprint $table) {
            $table->foreignId('maintenance_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['maintenance_role_id', 'user_id']);
            $table->timestamps();
        });

        // Rimozione della vecchia FK e colonna singola
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['maintenance_role_id']);
            $table->dropColumn('maintenance_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('maintenance_role_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('maintenance_role_user');
    }
};
