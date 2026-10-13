<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_growth_probes')) {
            return;
        }

        Schema::create('ai_growth_probes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('ai_visibility_run_id')->nullable()->constrained('ai_visibility_runs')->nullOnDelete();
            $table->text('question');
            $table->string('status', 32)->default('queued')->index();
            $table->string('verdict', 32)->nullable()->index();
            $table->boolean('brand_mentioned')->default(false);
            $table->boolean('owned_content_mentioned')->default(false);
            $table->text('answer_excerpt')->nullable();
            $table->json('analysis_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_growth_probes');
    }
};
