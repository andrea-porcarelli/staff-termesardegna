<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('initiated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();

            $table->index(['intervention_id', 'transferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_transfers');
    }
};
