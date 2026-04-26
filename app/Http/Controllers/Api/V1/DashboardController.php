<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardSnapshotService $snapshots) {}

    /**
     * GET /api/v1/dashboard
     *
     * Query: inicio (Y-m-d, default = início do mês),
     *        fim    (Y-m-d, default = fim do mês),
     *        familiar_id (int, optional).
     *
     * Returns the full month KPI snapshot (saldos, cartões, lançamentos).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'inicio'      => ['nullable', 'date_format:Y-m-d'],
            'fim'         => ['nullable', 'date_format:Y-m-d', 'after_or_equal:inicio'],
            'familiar_id' => ['nullable', 'integer'],
        ]);

        $data = $this->snapshots->snapshot(
            tenantId:   $request->user()->tenant_id,
            inicio:     $request->query('inicio'),
            fim:        $request->query('fim'),
            familiarId: $request->query('familiar_id') ? (int) $request->query('familiar_id') : null,
        );

        return response()->json($data);
    }
}
