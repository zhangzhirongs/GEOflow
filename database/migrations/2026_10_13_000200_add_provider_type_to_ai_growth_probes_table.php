<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_growth_probes') || Schema::hasColumn('ai_growth_probes', 'provider_type')) {
            return;
        }

        Schema::table('ai_growth_probes', function (Blueprint $table): void {
            $table->string('provider_type', 64)->default('auto')->after('question')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_growth_probes') || ! Schema::hasColumn('ai_growth_probes', 'provider_type')) {
            return;
        }

        Schema::table('ai_growth_probes', function (Blueprint $table): void {
            $table->dropColumn('provider_type');
        });
    }
};
