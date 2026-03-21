<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('draft', 'completed', 'chiuso') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('draft', 'completed') NOT NULL DEFAULT 'draft'");
    }
};
