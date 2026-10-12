<?php

namespace App\Providers;

use App\Http\Controllers\Admin\ExecutorNodeController;
use App\Http\Controllers\Admin\ManualPublicationSettingsController;
use App\Http\Controllers\Api\GeoRankNodeController;
use App\Http\Middleware\AuthenticateGeoRankNode;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GeoRankExecutorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // database/migrations 已由框架默认加载，无需重复注册（重复注册易在排查迁移时造成误解）。
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/georank.php'));

        $adminPrefix = trim((string) config('geoflow.admin_base_path', '/geo_admin'), '/');
        Route::middleware(['web', 'admin.locale', 'admin.auth', 'admin.activity', 'admin.super'])
            ->prefix($adminPrefix)
            ->name('admin.')
            ->group(function (): void {
                Route::get('executor-nodes', [ExecutorNodeController::class, 'index'])->name('executor-nodes.index');
                Route::post('executor-nodes', [ExecutorNodeController::class, 'store'])->name('executor-nodes.store');
                Route::post('executor-nodes/{nodeId}/reset-key', [ExecutorNodeController::class, 'resetKey'])->name('executor-nodes.reset-key')->whereNumber('nodeId');
                Route::delete('executor-nodes/{nodeId}', [ExecutorNodeController::class, 'destroy'])->name('executor-nodes.destroy')->whereNumber('nodeId');
                Route::post('manual-publications/settings/accounts/{accountId}/executor-login', [ManualPublicationSettingsController::class, 'startExecutorLogin'])->name('manual-publications.settings.accounts.executor-login')->whereNumber('accountId');
                Route::post('manual-publications/settings/accounts/{accountId}/executor-check', [ManualPublicationSettingsController::class, 'startExecutorCheck'])->name('manual-publications.settings.accounts.executor-check')->whereNumber('accountId');
            });
    }
}
