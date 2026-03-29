<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncController extends Controller
{
    /**
     * POST /api/sync/despesa
     * Replays a queued offline despesa from Background Sync.
     */
    public function despesa(Request $request)
    {
        $data = $request->validate([
            'descricao'         => 'required|string|max:255',
            'valor'             => 'required|numeric|min:0.01',
            'data_compra'       => 'required|date',
            'categoria_id'      => 'nullable|integer',
            'forma_pagamento'   => 'nullable|integer',
            'quem_comprou'      => 'nullable|integer',
            'recorrente'        => 'nullable|boolean',
            'observacao'        => 'nullable|string|max:500',
            'client_queue_id'   => 'nullable|string', // idempotency key from IDB
        ]);

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        // Idempotency: prevent double-sync of same queued item
        if (!empty($data['client_queue_id'])) {
            $exists = DB::table('despesas')
                ->where('tenant_id', $tenantId)
                ->where('client_queue_id', $data['client_queue_id'])
                ->exists();

            if ($exists) {
                return response()->json(['ok' => true, 'skipped' => true]);
            }
        }

        $id = DB::table('despesas')->insertGetId([
            'tenant_id'       => $tenantId,
            'user_id'         => $userId,
            'descricao'       => $data['descricao'],
            'valor'           => $data['valor'],
            'data_compra'     => $data['data_compra'],
            'categoria_id'    => $data['categoria_id'] ?? null,
            'forma_pagamento' => $data['forma_pagamento'] ?? null,
            'quem_comprou'    => $data['quem_comprou'] ?? null,
            'recorrente'      => $data['recorrente'] ?? false,
            'observacao'      => $data['observacao'] ?? null,
            'client_queue_id' => $data['client_queue_id'] ?? null,
            'origem'          => 'offline_sync',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    /**
     * POST /api/sync/receita
     * Replays a queued offline receita from Background Sync.
     */
    public function receita(Request $request)
    {
        $data = $request->validate([
            'descricao'                  => 'required|string|max:255',
            'valor'                      => 'required|numeric|min:0.01',
            'data_prevista_recebimento'  => 'required|date',
            'categoria_id'               => 'nullable|integer',
            'quem_recebeu'               => 'nullable|integer',
            'recorrente'                 => 'nullable|boolean',
            'observacao'                 => 'nullable|string|max:500',
            'client_queue_id'            => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        if (!empty($data['client_queue_id'])) {
            $exists = DB::table('receitas')
                ->where('tenant_id', $tenantId)
                ->where('client_queue_id', $data['client_queue_id'])
                ->exists();

            if ($exists) {
                return response()->json(['ok' => true, 'skipped' => true]);
            }
        }

        $id = DB::table('receitas')->insertGetId([
            'tenant_id'                 => $tenantId,
            'user_id'                   => $userId,
            'descricao'                 => $data['descricao'],
            'valor'                     => $data['valor'],
            'data_prevista_recebimento' => $data['data_prevista_recebimento'],
            'categoria_id'              => $data['categoria_id'] ?? null,
            'quem_recebeu'              => $data['quem_recebeu'] ?? null,
            'recorrente'                => $data['recorrente'] ?? false,
            'observacao'                => $data['observacao'] ?? null,
            'client_queue_id'           => $data['client_queue_id'] ?? null,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id]);
    }
}
