<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('manual_publication_account_id')->constrained('manual_publication_accounts')->cascadeOnDelete();
            $table->string('platform', 60)->index();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->string('remote_id', 120)->nullable();
            $table->string('remote_url', 1000)->nullable();
            $table->char('payload_hash', 64)->nullable()->index();
            $table->json('payload_snapshot')->nullable();
            $table->json('remote_meta')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->index(['manual_publication_account_id', 'status']);
            $table->index(['article_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publications');
    }
};
