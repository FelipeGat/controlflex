<?php

namespace Database\Seeders;

use App\Models\Banco;

class BancosDefaultSeeder
{
    // 15 bancos + carteira, cada um com nome, logo (arquivo em /img/bancos/), cor e código bancário
    public static array $bancos = [
        ['codigo_banco' => '341',  'nome' => 'Itaú',          'logo' => 'itau.svg',       'cor' => '#FF6600'],
        ['codigo_banco' => '237',  'nome' => 'Bradesco',       'logo' => 'bradesco.svg',   'cor' => '#CC0000'],
        ['codigo_banco' => '001',  'nome' => 'Banco do Brasil','logo' => 'bb.svg',         'cor' => '#FFCC00'],
        ['codigo_banco' => '104',  'nome' => 'Caixa',          'logo' => 'caixa.svg',      'cor' => '#0070AF'],
        ['codigo_banco' => '033',  'nome' => 'Santander',      'logo' => 'santander.svg',  'cor' => '#EC0000'],
        ['codigo_banco' => '260',  'nome' => 'Nubank',         'logo' => 'nubank.svg',     'cor' => '#8A05BE'],
        ['codigo_banco' => '077',  'nome' => 'Inter',          'logo' => 'inter.svg',      'cor' => '#FF6600'],
        ['codigo_banco' => '336',  'nome' => 'C6 Bank',        'logo' => 'c6.svg',         'cor' => '#242424'],
        ['codigo_banco' => '208',  'nome' => 'BTG Pactual',    'logo' => 'btg.svg',        'cor' => '#0A2240'],
        ['codigo_banco' => '102',  'nome' => 'XP',             'logo' => 'xp.svg',         'cor' => '#000000'],
        ['codigo_banco' => '380',  'nome' => 'PicPay',         'logo' => 'picpay.svg',     'cor' => '#21C25E'],
        ['codigo_banco' => '323',  'nome' => 'Mercado Pago',   'logo' => 'mercadopago.svg','cor' => '#009EE3'],
        ['codigo_banco' => '756',  'nome' => 'Sicoob',         'logo' => 'sicoob.svg',     'cor' => '#008E5A'],
        ['codigo_banco' => '748',  'nome' => 'Sicredi',        'logo' => 'sicredi.svg',    'cor' => '#5DAA31'],
        ['codigo_banco' => '422',  'nome' => 'Safra',          'logo' => 'safra.svg',      'cor' => '#1B3A6B'],
        ['codigo_banco' => null,   'nome' => 'Carteira',       'logo' => 'carteira.svg',   'cor' => '#6B7280'],
    ];

    public static function seedParaTenant(int $tenantId, int $userId): void
    {
        foreach (self::$bancos as $b) {
            $exists = Banco::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('nome', $b['nome'])
                ->exists();

            if ($exists) {
                continue;
            }

            $ehCarteira = $b['codigo_banco'] === null;

            Banco::withoutGlobalScopes()->create([
                'tenant_id'          => $tenantId,
                'user_id'            => $userId,
                'nome'               => $b['nome'],
                'codigo_banco'       => $b['codigo_banco'],
                'logo'               => $b['logo'],
                'cor'                => $b['cor'],
                'tem_conta_corrente' => !$ehCarteira,
                'tem_poupanca'       => false,
                'tem_cartao_credito' => false,
                'eh_dinheiro'        => $ehCarteira,
                'saldo'              => 0,
                'saldo_poupanca'     => 0,
                'cheque_especial'    => 0,
                'saldo_cheque'       => 0,
                'limite_cartao'      => 0,
                'saldo_cartao'       => 0,
            ]);
        }
    }
}
