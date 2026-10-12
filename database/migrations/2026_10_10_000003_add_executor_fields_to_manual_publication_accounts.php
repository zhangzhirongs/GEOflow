<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_publication_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('manual_publication_accounts', 'executor_node_id')) {
                $table->foreignId('executor_node_id')->nullable()->after('persona_id')->constrained('executor_nodes')->nullOnDelete();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'session_status')) {
                $table->string('session_status', 32)->default('missing')->index();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'session_checked_at')) {
                $table->timestamp('session_checked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_publication_accounts', function (Blueprint $table): void {
            if (Schema::hasColumn('manual_publication_accounts', 'executor_node_id')) {
                $table->dropConstrainedForeignId('executor_node_id');
            }
            foreach (['session_status', 'session_checked_at'] as $column) {
                if (Schema::hasColumn('manual_publication_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
