<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria índices compostos para as queries de listagem e dashboard.
     * A migration só adiciona índices nas tabelas que já possuem a coluna user_id,
     * garantindo compatibilidade com bancos criados via migrate:fresh.
     */
    public function up(): void
    {
        $this->addIndexIfColumnExists('despesas', ['user_id', 'data_compra'],              'despesas_user_data_compra_idx');
        $this->addIndexIfColumnExists('despesas', ['user_id', 'data_pagamento'],           'despesas_user_data_pagamento_idx');
        $this->addIndexIfColumnExists('receitas', ['user_id', 'data_prevista_recebimento'], 'receitas_user_data_prevista_idx');
        $this->addIndexIfColumnExists('receitas', ['user_id', 'data_recebimento'],         'receitas_user_data_recebimento_idx');
        $this->addIndexIfColumnExists('investimentos', ['user_id', 'data_aporte'],         'investimentos_user_data_aporte_idx');
        $this->addIndexIfColumnExists('bancos',      ['user_id'],                          'bancos_user_id_idx');
        $this->addIndexIfColumnExists('categorias',  ['user_id', 'tipo'],                  'categorias_user_tipo_idx');
        $this->addIndexIfColumnExists('familiares',  ['user_id'],                          'familiares_user_id_idx');
        $this->addIndexIfColumnExists('fornecedores', ['user_id'],                         'fornecedores_user_id_idx');
    }

    public function down(): void
    {
        $indexes = [
            'despesas'     => ['despesas_user_data_compra_idx', 'despesas_user_data_pagamento_idx'],
            'receitas'     => ['receitas_user_data_prevista_idx', 'receitas_user_data_recebimento_idx'],
            'investimentos'=> ['investimentos_user_data_aporte_idx'],
            'bancos'       => ['bancos_user_id_idx'],
            'categorias'   => ['categorias_user_tipo_idx'],
            'familiares'   => ['familiares_user_id_idx'],
            'fornecedores' => ['fornecedores_user_id_idx'],
        ];

        foreach ($indexes as $table => $names) {
            foreach ($names as $name) {
                $this->dropIndexIfExists($table, $name);
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function addIndexIfColumnExists(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasColumn($table, $columns[0])) {
            return;
        }

        // Para compatibilidade com SQLite, apenas tenta criar o índice se a coluna existir
        // O Laravel/Blueprint ignora se o índice já existe

        Schema::table($table, function (Blueprint $bp) use ($columns, $indexName) {
            $bp->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        // Para compatibilidade com SQLite, apenas tenta dropar o índice
        // O Laravel/Blueprint ignora se o índice não existe
        Schema::table($table, function (Blueprint $bp) use ($indexName) {
            $bp->dropIndex($indexName);
        });
    }
};
