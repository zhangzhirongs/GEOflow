<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('executor_jobs', 'manual_publication_id')) {
            Schema::table('executor_jobs', function (Blueprint $table): void {
                $table->foreignId('manual_publication_id')->nullable()->after('social_publication_id')->constrained('manual_publications')->nullOnDelete();
                $table->index(['manual_publication_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('executor_jobs', 'manual_publication_id')) {
            Schema::table('executor_jobs', function (Blueprint $table): void {
                $table->dropForeign(['manual_publication_id']);
                $table->dropIndex(['manual_publication_id', 'status']);
                $table->dropColumn('manual_publication_id');
            });
        }
    }
};
