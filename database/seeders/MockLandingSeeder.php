<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Despesa;
use App\Models\Familiar;
use App\Models\Investimento;
use App\Models\Receita;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Popula um tenant "Demo AlfaHome" com dados fictícios pensados
 * especificamente para as capturas de tela da landing page (hero dark/light).
 *
 * Rodar: php artisan db:seed --class=MockLandingSeeder
 * Usuário gerado: demo@alfahome.test / password
 */
class MockLandingSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::withoutGlobalScopes()->updateOrCreate(
            ['nome' => 'Demo AlfaHome'],
            ['ativo' => true, 'status' => 'ativo']
        );

        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'demo@alfahome.test'],
            [
                'name'     => 'Mariana Costa',
                'password' => Hash::make('password'),
                'role'     => 'master',
                'ativo'    => true,
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]
        );

        CategoriasDefaultSeeder::seedParaTenant($tenant->id, $user->id);
        BancosDefaultSeeder::seedParaTenant($tenant->id, $user->id);

        // ── Familiares ─────────────────────────────────────────────────
        $familiares = collect([
            ['nome' => 'Mariana', 'salario' => 8500],
            ['nome' => 'Rafael',  'salario' => 6200],
            ['nome' => 'Luísa',   'salario' => 0],
            ['nome' => 'Pedro',   'salario' => 0],
        ])->map(fn ($f) => Familiar::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'nome' => $f['nome']],
            ['user_id' => $user->id, 'salario' => $f['salario']]
        ));

        // ── Configurar bancos com saldos e cartões de crédito ──────────
        $nubank = Banco::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('nome', 'Nubank')->first();
        if ($nubank) {
            $nubank->update([
                'titular_id' => $familiares[0]->id,
                'tipo_conta' => 'ambos',
                'tem_conta_corrente' => true,
                'tem_cartao_credito' => true,
                'saldo' => 12480.55,
                'limite_cartao' => 15000,
                'dia_vencimento_cartao' => 10,
                'dia_fechamento_cartao' => 3,
            ]);
        }

        $itau = Banco::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('nome', 'Itaú')->first();
        if ($itau) {
            $itau->update([
                'titular_id' => $familiares[1]->id,
                'tipo_conta' => 'ambos',
                'tem_conta_corrente' => true,
                'tem_cartao_credito' => true,
                'saldo' => 5320.10,
                'limite_cartao' => 8000,
                'dia_vencimento_cartao' => 15,
                'dia_fechamento_cartao' => 8,
            ]);
        }

        $inter = Banco::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('nome', 'Inter')->first();
        if ($inter) {
            $inter->update([
                'titular_id' => $familiares[0]->id,
                'tipo_conta' => 'conta_corrente',
                'tem_conta_corrente' => true,
                'tem_cartao_credito' => false,
                'saldo' => 2150.00,
            ]);
        }

        // ── Helper: busca categoria por nome exato (tipo em uppercase) ─
        $cat = fn (string $nome, string $tipo) => Categoria::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('nome', $nome)
            ->where('tipo', strtoupper($tipo))
            ->first();

        $mesAtual = now()->startOfMonth();

        // ── Despesas do mês (categorias existem nos defaults) ─────────
        $despesas = [
            ['dia' =>  2, 'onde' => 'Condomínio Edifício Vista',    'cat' => 'Condomínio',              'valor' => 1850.00, 'pago' => true,  'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' =>  3, 'onde' => 'Supermercado Pão de Açúcar',   'cat' => 'Supermercado / Feira',    'valor' => 687.30,  'pago' => true,  'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' =>  4, 'onde' => 'Posto Shell',                  'cat' => 'Combustível',             'valor' => 320.00,  'pago' => true,  'quem' => 1, 'forma' => $itau?->id,   'tipo' => 'credito'],
            ['dia' =>  5, 'onde' => 'Farmácia São João',            'cat' => 'Farmácia / Remédios',     'valor' => 142.80,  'pago' => true,  'quem' => 0, 'forma' => $inter?->id,  'tipo' => 'debito'],
            ['dia' =>  6, 'onde' => 'Escola Arco-Íris',             'cat' => 'Escola / Faculdade',      'valor' => 1420.00, 'pago' => true,  'quem' => 0, 'forma' => $inter?->id,  'tipo' => 'debito'],
            ['dia' =>  8, 'onde' => 'Netflix',                      'cat' => 'Streaming (Netflix, Spotify)', 'valor' => 55.90, 'pago' => true, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' =>  9, 'onde' => 'Uber',                         'cat' => 'Uber / 99 / Táxi',        'valor' => 42.50,   'pago' => true,  'quem' => 1, 'forma' => $itau?->id,   'tipo' => 'credito'],
            ['dia' => 10, 'onde' => 'Restaurante Villa Nobre',      'cat' => 'Restaurante / Delivery',  'valor' => 238.70,  'pago' => true,  'quem' => 1, 'forma' => $itau?->id,   'tipo' => 'credito'],
            ['dia' => 11, 'onde' => 'Academia Smart Fit',           'cat' => 'Academia / Esportes',     'valor' => 99.90,   'pago' => true,  'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 12, 'onde' => 'Enel Energia',                 'cat' => 'Energia Elétrica',        'valor' => 312.45,  'pago' => true,  'quem' => 0, 'forma' => $inter?->id,  'tipo' => 'debito'],
            ['dia' => 13, 'onde' => 'Sabesp',                       'cat' => 'Água e Esgoto',           'valor' => 148.20,  'pago' => true,  'quem' => 0, 'forma' => $inter?->id,  'tipo' => 'debito'],
            ['dia' => 14, 'onde' => 'Vivo Fibra',                   'cat' => 'Internet e TV',           'valor' => 119.90,  'pago' => true,  'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 15, 'onde' => 'Drogasil',                     'cat' => 'Farmácia / Remédios',     'valor' => 87.40,   'pago' => false, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 16, 'onde' => 'Amazon',                       'cat' => 'Roupas / Calçados',       'valor' => 418.00,  'pago' => false, 'quem' => 1, 'forma' => $itau?->id,   'tipo' => 'credito'],
            ['dia' => 18, 'onde' => 'Cinépolis',                    'cat' => 'Cinema / Teatro / Show',  'valor' => 124.00,  'pago' => false, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 19, 'onde' => 'Pet Shop Amigo Fiel',          'cat' => 'Vet / Pet Shop',          'valor' => 186.50,  'pago' => false, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 20, 'onde' => 'Supermercado Extra',           'cat' => 'Supermercado / Feira',    'valor' => 432.15,  'pago' => false, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 22, 'onde' => 'Posto Ipiranga',               'cat' => 'Combustível',             'valor' => 280.00,  'pago' => false, 'quem' => 1, 'forma' => $itau?->id,   'tipo' => 'credito'],
            ['dia' => 24, 'onde' => 'Spotify Premium',              'cat' => 'Streaming (Netflix, Spotify)', 'valor' => 34.90, 'pago' => false, 'quem' => 0, 'forma' => $nubank?->id, 'tipo' => 'credito'],
            ['dia' => 26, 'onde' => 'Padaria Bella Massa',          'cat' => 'Padaria e Café',          'valor' => 178.60,  'pago' => false, 'quem' => 0, 'forma' => $inter?->id,  'tipo' => 'debito'],
        ];

        Despesa::withoutGlobalScopes()->where('tenant_id', $tenant->id)->forceDelete();
        foreach ($despesas as $d) {
            $categoria = $cat($d['cat'], 'despesa');
            if (!$categoria) continue;
            $dataCompra = $mesAtual->copy()->addDays($d['dia'] - 1);
            try {
                Despesa::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'observacoes' => $d['onde'],
                    'quem_comprou' => $familiares[$d['quem']]->id,
                    'categoria_id' => $categoria->id,
                    'forma_pagamento' => $d['forma'],
                    'tipo_pagamento' => $d['tipo'],
                    'valor' => $d['valor'],
                    'data_compra' => $dataCompra->format('Y-m-d'),
                    'data_pagamento' => $d['pago'] ? $dataCompra->format('Y-m-d') : null,
                    'origem' => 'manual',
                ]);
            } catch (\Throwable $e) {
                echo "DESPESA FAIL ({$d['onde']}): " . $e->getMessage() . "\n";
            }
        }

        // ── Receitas do mês ────────────────────────────────────────────
        $receitas = [
            ['dia' =>  5, 'cat' => 'Salário',                'valor' => 8500.00, 'quem' => 0, 'recebida' => true],
            ['dia' =>  5, 'cat' => 'Salário',                'valor' => 6200.00, 'quem' => 1, 'recebida' => true],
            ['dia' => 10, 'cat' => 'Freelance / Bico',       'valor' => 1800.00, 'quem' => 0, 'recebida' => true],
            ['dia' => 15, 'cat' => 'Aluguel de Imóvel',      'valor' => 2200.00, 'quem' => 0, 'recebida' => true],
            ['dia' => 20, 'cat' => 'Rendimento CDB / Tesouro','valor' => 485.30,  'quem' => 0, 'recebida' => true],
            ['dia' => 22, 'cat' => 'Freelance / Bico',       'valor' => 950.00,  'quem' => 1, 'recebida' => false],
            ['dia' => 25, 'cat' => 'Bônus / PLR',            'valor' => 1200.00, 'quem' => 1, 'recebida' => false],
            ['dia' => 28, 'cat' => 'Rendimento CDB / Tesouro','valor' => 320.00,  'quem' => 0, 'recebida' => false],
        ];

        Receita::withoutGlobalScopes()->where('tenant_id', $tenant->id)->forceDelete();
        foreach ($receitas as $r) {
            $categoria = $cat($r['cat'], 'receita');
            if (!$categoria) continue;
            $dataPrevista = $mesAtual->copy()->addDays($r['dia'] - 1);
            try {
                Receita::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'categoria_id' => $categoria->id,
                    'quem_recebeu' => $familiares[$r['quem']]->id,
                    'valor' => $r['valor'],
                    'data_prevista_recebimento' => $dataPrevista->format('Y-m-d'),
                    'data_recebimento' => $r['recebida'] ? $dataPrevista->format('Y-m-d') : null,
                    'forma_recebimento' => $inter?->id,
                    'tipo_pagamento' => 'pix',
                ]);
            } catch (\Throwable $e) {
                echo "RECEITA FAIL ({$r['cat']} {$r['valor']}): " . $e->getMessage() . "\n";
            }
        }

        // ── Investimentos (aportes nos últimos 12 meses) ───────────────
        $aportes = [2000, 2500, 2200, 3000, 2800, 3500, 3200, 4000, 3500, 4500, 4200, 5000];
        foreach ($aportes as $i => $valor) {
            $dataAporte = now()->subMonths(11 - $i)->startOfMonth()->addDays(4);
            Investimento::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'nome_ativo' => 'Tesouro Selic 2029',
                    'data_aporte' => $dataAporte->format('Y-m-d'),
                ],
                [
                    'user_id' => $user->id,
                    'banco_id' => $itau?->id,
                    'tipo_investimento' => 'renda_fixa',
                    'valor_aportado' => $valor,
                    'percentual_anual' => 12,
                ]
            );
        }
    }
}
