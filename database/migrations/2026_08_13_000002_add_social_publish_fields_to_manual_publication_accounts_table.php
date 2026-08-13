<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_publication_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('manual_publication_accounts', 'auto_publish_enabled')) {
                $table->boolean('auto_publish_enabled')->default(false)->index();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_adapter')) {
                $table->string('publish_adapter', 60)->nullable()->index();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_endpoint_url')) {
                $table->string('publish_endpoint_url', 1000)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_method')) {
                $table->string('publish_method', 10)->default('POST');
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_auth_type')) {
                $table->string('publish_auth_type', 20)->default('none');
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_login_identifier')) {
                $table->string('publish_login_identifier', 160)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_auth_header_name')) {
                $table->string('publish_auth_header_name', 120)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_basic_username')) {
                $table->string('publish_basic_username', 160)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_secret_key_id')) {
                $table->string('publish_secret_key_id', 120)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_secret_ciphertext')) {
                $table->text('publish_secret_ciphertext')->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_session_ciphertext')) {
                $table->text('publish_session_ciphertext')->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_profile_json')) {
                $table->json('publish_profile_json')->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_status')) {
                $table->string('publish_status', 20)->default('idle')->index();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'publish_attempt_count')) {
                $table->unsignedInteger('publish_attempt_count')->default(0);
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'last_published_at')) {
                $table->timestamp('last_published_at')->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'next_publish_at')) {
                $table->timestamp('next_publish_at')->nullable()->index();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'last_publish_status')) {
                $table->string('last_publish_status', 20)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'last_publish_error')) {
                $table->text('last_publish_error')->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'last_remote_id')) {
                $table->string('last_remote_id', 120)->nullable();
            }
            if (! Schema::hasColumn('manual_publication_accounts', 'last_remote_url')) {
                $table->string('last_remote_url', 1000)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_publication_accounts', function (Blueprint $table): void {
            foreach ([
                'auto_publish_enabled',
                'publish_adapter',
                'publish_endpoint_url',
                'publish_method',
                'publish_auth_type',
                'publish_login_identifier',
                'publish_auth_header_name',
                'publish_basic_username',
                'publish_secret_key_id',
                'publish_secret_ciphertext',
                'publish_session_ciphertext',
                'publish_profile_json',
                'publish_status',
                'publish_attempt_count',
                'last_published_at',
                'next_publish_at',
                'last_publish_status',
                'last_publish_error',
                'last_remote_id',
                'last_remote_url',
            ] as $column) {
                if (Schema::hasColumn('manual_publication_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
