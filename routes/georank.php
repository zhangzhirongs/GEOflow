<?php

use App\Http\Controllers\Api\GeoRankNodeController;
use App\Http\Middleware\AuthenticateGeoRankNode;
use Illuminate\Support\Facades\Route;

Route::prefix('node')
    ->middleware([AuthenticateGeoRankNode::class, 'throttle:60,1'])
    ->group(function (): void {
        Route::post('heartbeat', [GeoRankNodeController::class, 'heartbeat']);
        Route::post('jobs/claim', [GeoRankNodeController::class, 'claim']);
        Route::post('jobs/{id}/report', [GeoRankNodeController::class, 'report'])->whereNumber('id');
    });
