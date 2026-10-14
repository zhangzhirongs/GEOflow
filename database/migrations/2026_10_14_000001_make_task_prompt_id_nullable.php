<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'prompt_id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE tasks ALTER COLUMN prompt_id DROP NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE tasks MODIFY prompt_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Existing rows without a prompt cannot be made non-null safely.
    }
};
