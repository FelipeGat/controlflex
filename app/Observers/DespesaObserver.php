<?php

namespace App\Observers;

use App\Models\Banco;
use App\Models\Despesa;

class DespesaObserver
{
    public function created(Despesa $despesa): void
    {
        // Despesa criada já como paga → debitar saldo do banco
        if ($despesa->data_pagamento && $despesa->tipo_pagamento !== 'credito') {
            $this->debitarBanco($despesa);
        }
    }

    public function updating(Despesa $despesa): void
    {
        if (! $despesa->isDirty('data_pagamento')) {
            return;
        }

        // Cartão de crédito não afeta saldo da conta corrente
        if ($despesa->tipo_pagamento === 'credito') {
            return;
        }

        $antigo = $despesa->getOriginal('data_pagamento');
        $novo   = $despesa->data_pagamento;

        if (is_null($antigo) && ! is_null($novo)) {
            // Marcou como paga → debitar
            $this->debitarBanco($despesa);
        } elseif (! is_null($antigo) && is_null($novo)) {
            // Estornou → creditar
            $this->creditarBanco($despesa);
        }
    }

    public function deleted(Despesa $despesa): void
    {
        // Se a despesa excluída estava paga, devolver o saldo
        if ($despesa->data_pagamento && $despesa->tipo_pagamento !== 'credito') {
            $this->creditarBanco($despesa);
        }
    }

    private function debitarBanco(Despesa $despesa): void
    {
        $banco = Banco::find($despesa->forma_pagamento);
        if ($banco) {
            $banco->decrement('saldo', (float) $despesa->valor);
        }
    }

    private function creditarBanco(Despesa $despesa): void
    {
        $banco = Banco::find($despesa->forma_pagamento);
        if ($banco) {
            $banco->increment('saldo', (float) $despesa->valor);
        }
    }
}
