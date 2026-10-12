<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('executor_jobs')) {
            return;
        }

        Schema::create('executor_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('status', 20)->default('queued')->index();
            $table->foreignId('executor_node_id')->nullable()->constrained('executor_nodes')->nullOnDelete();
            $table->foreignId('manual_publication_account_id')->constrained('manual_publication_accounts')->cascadeOnDelete();
            $table->foreignId('social_publication_id')->nullable()->constrained('social_publications')->nullOnDelete();
            $table->string('platform', 40)->index();
            $table->json('recipe')->nullable();
            $table->json('payload')->nullable();
            $table->string('login_identifier', 160)->nullable();
            $table->text('error')->nullable();
            $table->string('remote_id', 160)->nullable();
            $table->string('remote_url', 1000)->nullable();
            $table->json('logs')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executor_jobs');
    }
};
