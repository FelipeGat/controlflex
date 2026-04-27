<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogosController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DespesaController as ApiDespesaController;
use App\Http\Controllers\Api\V1\ReceitaController as ApiReceitaController;

/*
|--------------------------------------------------------------------------
| API Routes — AlfaHome PWA (legacy / session-based)
|--------------------------------------------------------------------------
| These routes are stateless from the CSRF point of view but rely on the
| web session cookie. They power the existing PWA Service Worker and are
| kept untouched for backwards compatibility.
*/

Route::middleware(['auth', 'tenant.ativo'])->group(function () {

    Route::get('/dashboard/snapshot', [DashboardApiController::class, 'snapshot'])
        ->name('api.dashboard.snapshot');

    Route::post('/push/subscribe',   [PushController::class, 'subscribe'])
        ->name('api.push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])
        ->name('api.push.unsubscribe');

    Route::post('/sync/despesa',  [App\Http\Controllers\Api\SyncController::class, 'despesa'])
        ->name('api.sync.despesa');
    Route::post('/sync/receita',  [App\Http\Controllers\Api\SyncController::class, 'receita'])
        ->name('api.sync.receita');

});

/*
|--------------------------------------------------------------------------
| API Routes V1 — AlfaHome Mobile App (Sanctum, token-based)
|--------------------------------------------------------------------------
| Stateless REST API consumed by the Flutter mobile app.
| Authentication: Bearer token (Laravel Sanctum personal access tokens).
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Public ───────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // ── Authenticated (Sanctum) ──────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'tenant.ativo.api'])->group(function () {

        // Auth
        Route::get('auth/me',          [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout',     [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ── Catálogos (read-only, todos do tenant) ──────────────────────
        Route::get('categorias',   [CatalogosController::class, 'categorias'])->name('categorias.index');
        Route::get('familiares',   [CatalogosController::class, 'familiares'])->name('familiares.index');
        Route::get('fornecedores', [CatalogosController::class, 'fornecedores'])->name('fornecedores.index');
        Route::get('bancos',       [CatalogosController::class, 'bancos'])->name('bancos.index');

        // ── Despesas (CRUD) ──────────────────────────────────────────────
        Route::get(   'despesas',                   [ApiDespesaController::class, 'index'])->name('despesas.index');
        Route::post(  'despesas',                   [ApiDespesaController::class, 'store'])->name('despesas.store');
        Route::get(   'despesas/grupo/{grupoId}',   [ApiDespesaController::class, 'grupo'])->name('despesas.grupo');
        Route::get(   'despesas/{despesa}',         [ApiDespesaController::class, 'show'])->name('despesas.show');
        Route::put(   'despesas/{despesa}', [ApiDespesaController::class, 'update'])->name('despesas.update');
        Route::delete('despesas/{despesa}', [ApiDespesaController::class, 'destroy'])->name('despesas.destroy');

        // ── Receitas (CRUD) ──────────────────────────────────────────────
        Route::get(   'receitas',           [ApiReceitaController::class, 'index'])->name('receitas.index');
        Route::post(  'receitas',           [ApiReceitaController::class, 'store'])->name('receitas.store');
        Route::get(   'receitas/{receita}', [ApiReceitaController::class, 'show'])->name('receitas.show');
        Route::put(   'receitas/{receita}', [ApiReceitaController::class, 'update'])->name('receitas.update');
        Route::delete('receitas/{receita}', [ApiReceitaController::class, 'destroy'])->name('receitas.destroy');

    });

});
