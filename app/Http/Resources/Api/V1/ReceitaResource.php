<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'valor'                     => (float) $this->valor,
            'data_prevista_recebimento' => $this->data_prevista_recebimento?->format('Y-m-d'),
            'data_recebimento'          => $this->data_recebimento?->format('Y-m-d'),
            'tipo_pagamento'            => $this->tipo_pagamento,
            'status'                    => $this->status, // accessor
            'observacoes'               => $this->observacoes,
            'parcelas'                  => (int) $this->parcelas,
            'frequencia'                => $this->frequencia,
            'recorrente'                => (bool) $this->recorrente,
            'grupo_recorrencia_id'      => $this->grupo_recorrencia_id,

            'categoria_id'      => $this->categoria_id,
            'quem_recebeu'      => $this->quem_recebeu,
            'forma_recebimento' => $this->forma_recebimento,

            'categoria' => $this->whenLoaded('categoria', fn() => $this->categoria ? [
                'id'    => $this->categoria->id,
                'nome'  => $this->categoria->nome,
                'icone' => $this->categoria->icone,
            ] : null),
            'familiar'  => $this->whenLoaded('familiar',  fn() => $this->familiar ? [
                'id'   => $this->familiar->id,
                'nome' => $this->familiar->nome,
            ] : null),
            'banco'     => $this->whenLoaded('banco',     fn() => $this->banco ? [
                'id'   => $this->banco->id,
                'nome' => $this->banco->nome,
                'cor'  => $this->banco->cor,
            ] : null),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
