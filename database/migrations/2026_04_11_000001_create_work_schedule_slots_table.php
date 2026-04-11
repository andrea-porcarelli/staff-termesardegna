<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->tinyInteger('day_of_week')->unsigned()->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['lavorativo', 'ferie', 'riposi'])->default('lavorativo');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_slots');
    }
};
