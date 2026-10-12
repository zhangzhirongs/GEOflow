<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'target_platform')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table): void {
            // 任务按目标社媒平台生成文章（zhihu/xiaohongshu/...）；为空表示通用稿。
            $table->string('target_platform', 32)->nullable()->after('ai_model_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'target_platform')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('target_platform');
        });
    }
};
