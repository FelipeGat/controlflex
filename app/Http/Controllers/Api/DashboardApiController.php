<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
    public function __construct(private readonly DashboardSnapshotService $snapshots) {}

    /**
     * GET /api/dashboard/snapshot
     *
     * Lightweight JSON snapshot of the current month's KPIs.
     * Used by the PWA Service Worker for stale-while-revalidate caching.
     */
    public function snapshot(Request $request)
    {
        $data = $this->snapshots->snapshot(
            tenantId:   Auth::user()->tenant_id,
            inicio:     $request->get('inicio'),
            fim:        $request->get('fim'),
            familiarId: $request->get('familiar_id') ? (int) $request->get('familiar_id') : null,
        );

        return response()->json($data);
    }
}
