<?php

namespace App\Observers;

use App\Models\Banco;
use App\Models\Receita;

class ReceitaObserver
{
    public function created(Receita $receita): void
    {
        // Receita criada já como recebida → creditar saldo do banco
        if ($receita->data_recebimento) {
            $this->creditarBanco($receita);
        }
    }

    public function updating(Receita $receita): void
    {
        if (! $receita->isDirty('data_recebimento')) {
            return;
        }

        $antigo = $receita->getOriginal('data_recebimento');
        $novo   = $receita->data_recebimento;

        if (is_null($antigo) && ! is_null($novo)) {
            // Marcou como recebida → creditar
            $this->creditarBanco($receita);
        } elseif (! is_null($antigo) && is_null($novo)) {
            // Estornou → debitar
            $this->debitarBanco($receita);
        }
    }

    public function deleted(Receita $receita): void
    {
        // Se a receita excluída estava recebida, estornar o saldo
        if ($receita->data_recebimento) {
            $this->debitarBanco($receita);
        }
    }

    private function creditarBanco(Receita $receita): void
    {
        $banco = Banco::find($receita->forma_recebimento);
        if ($banco) {
            $banco->increment('saldo', (float) $receita->valor);
        }
    }

    private function debitarBanco(Receita $receita): void
    {
        $banco = Banco::find($receita->forma_recebimento);
        if ($banco) {
            $banco->decrement('saldo', (float) $receita->valor);
        }
    }
}
