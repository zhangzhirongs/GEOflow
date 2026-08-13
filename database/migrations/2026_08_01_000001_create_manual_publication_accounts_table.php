<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_publication_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('persona_id')->constrained('manual_publication_personas')->restrictOnDelete();
            $table->string('platform', 60)->index();
            $table->string('custom_platform', 120)->nullable();
            $table->string('account_name', 160);
            $table->string('profile_url', 1000)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('auto_publish_enabled')->default(false)->index();
            $table->string('publish_adapter', 60)->nullable()->index();
            $table->string('publish_endpoint_url', 1000)->nullable();
            $table->string('publish_method', 10)->default('POST');
            $table->string('publish_auth_type', 20)->default('none');
            $table->string('publish_login_identifier', 160)->nullable();
            $table->string('publish_auth_header_name', 120)->nullable();
            $table->string('publish_basic_username', 160)->nullable();
            $table->string('publish_secret_key_id', 120)->nullable();
            $table->text('publish_secret_ciphertext')->nullable();
            $table->text('publish_session_ciphertext')->nullable();
            $table->json('publish_profile_json')->nullable();
            $table->string('publish_status', 20)->default('idle')->index();
            $table->unsignedInteger('publish_attempt_count')->default(0);
            $table->timestamp('last_published_at')->nullable();
            $table->timestamp('next_publish_at')->nullable()->index();
            $table->string('last_publish_status', 20)->nullable();
            $table->text('last_publish_error')->nullable();
            $table->string('last_remote_id', 120)->nullable();
            $table->string('last_remote_url', 1000)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['persona_id', 'is_active']);
            $table->index(['platform', 'is_active']);
            $table->index(['platform', 'is_active', 'auto_publish_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_publication_accounts');
    }
};
