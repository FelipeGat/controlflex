<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Familiar;
use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Banco;
use App\Models\Despesa;
use App\Models\Receita;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Felipe',
            'email' => 'felipe@controleflex.com',
            'password' => Hash::make('password'),
        ]);

        // Familiares
        $felipe = Familiar::create(['user_id' => $user->id, 'nome' => 'Felipe', 'salario' => 5000, 'limite_cartao' => 3000, 'limite_cheque' => 1000]);
        $maria = Familiar::create(['user_id' => $user->id, 'nome' => 'Maria', 'salario' => 4000, 'limite_cartao' => 2000, 'limite_cheque' => 500]);

        // Categorias
        $cats = [
            ['nome' => 'Alimentação', 'tipo' => 'DESPESA', 'icone' => 'fa-utensils'],
            ['nome' => 'Moradia', 'tipo' => 'DESPESA', 'icone' => 'fa-house'],
            ['nome' => 'Transporte', 'tipo' => 'DESPESA', 'icone' => 'fa-car'],
            ['nome' => 'Saúde', 'tipo' => 'DESPESA', 'icone' => 'fa-heart-pulse'],
            ['nome' => 'Lazer', 'tipo' => 'DESPESA', 'icone' => 'fa-gamepad'],
            ['nome' => 'Educação', 'tipo' => 'DESPESA', 'icone' => 'fa-graduation-cap'],
            ['nome' => 'Roupas', 'tipo' => 'DESPESA', 'icone' => 'fa-shirt'],
            ['nome' => 'Outros', 'tipo' => 'DESPESA', 'icone' => 'fa-ellipsis'],
            ['nome' => 'Salário', 'tipo' => 'RECEITA', 'icone' => 'fa-briefcase'],
            ['nome' => 'Freelance', 'tipo' => 'RECEITA', 'icone' => 'fa-laptop'],
            ['nome' => 'Investimentos', 'tipo' => 'RECEITA', 'icone' => 'fa-chart-line'],
            ['nome' => 'Outros', 'tipo' => 'RECEITA', 'icone' => 'fa-ellipsis'],
        ];
        $categoriaMap = [];
        foreach ($cats as $cat) {
            $c = Categoria::create(array_merge($cat, ['user_id' => $user->id]));
            $categoriaMap[$cat['nome'] . '_' . $cat['tipo']] = $c->id;
        }

        // Fornecedores
        $supermercado = Fornecedor::create(['user_id' => $user->id, 'nome' => 'Supermercado Extra', 'telefone' => '(11) 3000-0000']);
        $farmacia = Fornecedor::create(['user_id' => $user->id, 'nome' => 'Farmácia Popular', 'telefone' => '(11) 3001-0000']);
        $restaurante = Fornecedor::create(['user_id' => $user->id, 'nome' => 'Restaurante do Zé', 'telefone' => '(11) 3002-0000']);
        $posto = Fornecedor::create(['user_id' => $user->id, 'nome' => 'Posto Shell', 'telefone' => '(11) 3003-0000']);

        // Bancos
        $nubank = Banco::create(['user_id' => $user->id, 'nome' => 'Nubank', 'tipo_conta' => 'Conta Corrente', 'saldo' => 3500.00, 'limite_cartao' => 5000, 'saldo_cartao' => 1200]);
        $itau = Banco::create(['user_id' => $user->id, 'nome' => 'Itaú', 'tipo_conta' => 'Conta Corrente', 'saldo' => 8200.00, 'cheque_especial' => 2000]);
        $carteira = Banco::create(['user_id' => $user->id, 'nome' => 'Carteira', 'tipo_conta' => 'Dinheiro', 'saldo' => 350.00]);

        // Despesas dos últimos 3 meses
        $despesasDados = [
            ['fornecedor' => $supermercado->id, 'cat' => $categoriaMap['Alimentação_DESPESA'], 'valor' => 450, 'familiar' => $felipe->id, 'banco' => $nubank->id],
            ['fornecedor' => $restaurante->id, 'cat' => $categoriaMap['Alimentação_DESPESA'], 'valor' => 180, 'familiar' => $maria->id, 'banco' => $nubank->id],
            ['fornecedor' => $farmacia->id, 'cat' => $categoriaMap['Saúde_DESPESA'], 'valor' => 95, 'familiar' => $felipe->id, 'banco' => $itau->id],
            ['fornecedor' => $posto->id, 'cat' => $categoriaMap['Transporte_DESPESA'], 'valor' => 220, 'familiar' => $felipe->id, 'banco' => $nubank->id],
            ['fornecedor' => $supermercado->id, 'cat' => $categoriaMap['Moradia_DESPESA'], 'valor' => 1500, 'familiar' => $felipe->id, 'banco' => $itau->id],
        ];

        for ($m = 0; $m < 3; $m++) {
            $data = Carbon::now()->subMonths($m)->format('Y-m-') . '10';
            foreach ($despesasDados as $d) {
                Despesa::create([
                    'user_id' => $user->id,
                    'quem_comprou' => $d['familiar'],
                    'onde_comprou' => $d['fornecedor'],
                    'categoria_id' => $d['cat'],
                    'forma_pagamento' => $d['banco'],
                    'valor' => $d['valor'],
                    'data_compra' => $data,
                    'data_pagamento' => $m > 0 ? $data : null,
                    'recorrente' => true,
                    'parcelas' => 3,
                    'frequencia' => 'mensal',
                    'grupo_recorrencia_id' => 'seed-' . $d['cat'],
                ]);
            }
        }

        // Receitas dos últimos 3 meses
        for ($m = 0; $m < 3; $m++) {
            $data = Carbon::now()->subMonths($m)->startOfMonth()->addDays(4)->format('Y-m-d');
            Receita::create([
                'user_id' => $user->id,
                'quem_recebeu' => $felipe->id,
                'categoria_id' => $categoriaMap['Salário_RECEITA'],
                'forma_recebimento' => $itau->id,
                'valor' => 5000,
                'data_prevista_recebimento' => $data,
                'data_recebimento' => $m > 0 ? $data : null,
                'recorrente' => true,
                'parcelas' => 3,
                'frequencia' => 'mensal',
                'grupo_recorrencia_id' => 'seed-salario-felipe',
            ]);
            Receita::create([
                'user_id' => $user->id,
                'quem_recebeu' => $maria->id,
                'categoria_id' => $categoriaMap['Salário_RECEITA'],
                'forma_recebimento' => $nubank->id,
                'valor' => 4000,
                'data_prevista_recebimento' => $data,
                'data_recebimento' => $m > 0 ? $data : null,
                'recorrente' => true,
                'parcelas' => 3,
                'frequencia' => 'mensal',
                'grupo_recorrencia_id' => 'seed-salario-maria',
            ]);
        }
    }
}
