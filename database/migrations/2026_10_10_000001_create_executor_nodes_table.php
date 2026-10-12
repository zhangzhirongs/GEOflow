<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('executor_nodes')) {
            return;
        }

        Schema::create('executor_nodes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 80)->unique();
            $table->string('node_key_hash', 64);
            $table->json('platforms')->nullable();
            $table->unsignedTinyInteger('max_concurrency')->default(1);
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('online')->default(false)->index();
            $table->unsignedInteger('load')->default(0);
            $table->string('ip', 64)->nullable();
            $table->string('version', 40)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable()->index();
            $table->unsignedInteger('done')->default(0);
            $table->unsignedInteger('fail')->default(0);
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executor_nodes');
    }
};
