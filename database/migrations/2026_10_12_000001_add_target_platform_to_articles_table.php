<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('articles', 'target_platform')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            // 生成文章时选定的目标社媒平台（zhihu/xiaohongshu/...）；为空表示通用稿。
            $table->string('target_platform', 32)->nullable()->after('meta_description')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('articles', 'target_platform')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn('target_platform');
        });
    }
};
