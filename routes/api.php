<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\PushController;

/*
|--------------------------------------------------------------------------
| API Routes — AlfaHome PWA
|--------------------------------------------------------------------------
| These routes are stateless (session-cookie auth via web guard).
| CSRF not enforced here (API middleware group).
*/

Route::middleware(['auth', 'tenant.ativo'])->group(function () {

    // ── Dashboard snapshot (for offline cache + stale-while-revalidate) ──
    Route::get('/dashboard/snapshot', [DashboardApiController::class, 'snapshot'])
        ->name('api.dashboard.snapshot');

    // ── Push Notifications ────────────────────────────────────────────────
    Route::post('/push/subscribe',   [PushController::class, 'subscribe'])
        ->name('api.push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])
        ->name('api.push.unsubscribe');

    // ── Background Sync replay endpoint ───────────────────────────────────
    Route::post('/sync/despesa',  [App\Http\Controllers\Api\SyncController::class, 'despesa'])
        ->name('api.sync.despesa');
    Route::post('/sync/receita',  [App\Http\Controllers\Api\SyncController::class, 'receita'])
        ->name('api.sync.receita');

});
