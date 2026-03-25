<?php

namespace Database\Seeders;

use App\Models\Fornecedor;

class FornecedoresDefaultSeeder
{
    public static function seedParaTenant(int $tenantId, int $userId): void
    {
        foreach (self::fornecedores() as $f) {
            $existe = Fornecedor::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('nome', $f['nome'])
                ->exists();

            if (!$existe) {
                Fornecedor::withoutGlobalScopes()->create(array_merge($f, [
                    'tenant_id' => $tenantId,
                    'user_id'   => $userId,
                ]));
            }
        }
    }

    public static function fornecedores(): array
    {
        return [
            // ══════════════════════════════════════════════════════
            // 🛒 Supermercados e Atacados
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Supermercados', 'nome' => 'Carrefour',            'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Extra',                'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Pão de Açúcar',        'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Atacadão',             'icone' => 'fa-boxes-stacked'],
            ['grupo' => 'Supermercados', 'nome' => 'Assaí Atacadista',     'icone' => 'fa-boxes-stacked'],
            ['grupo' => 'Supermercados', 'nome' => 'Dia Supermercados',    'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Supermercado BIG',     'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Sonda Supermercados',  'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Hirota Food',          'icone' => 'fa-cart-shopping'],
            ['grupo' => 'Supermercados', 'nome' => 'Comper',               'icone' => 'fa-cart-shopping'],

            // ══════════════════════════════════════════════════════
            // 💊 Farmácias e Drogarias
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Farmácias', 'nome' => 'Droga Raia',           'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Drogasil',             'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Ultrafarma',           'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Farmácias Nissei',     'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Drogaria São Paulo',   'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Pacheco',              'icone' => 'fa-pills'],
            ['grupo' => 'Farmácias', 'nome' => 'Panvel',               'icone' => 'fa-pills'],

            // ══════════════════════════════════════════════════════
            // ⛽ Combustível e Postos
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Combustível', 'nome' => 'Posto Shell',        'icone' => 'fa-gas-pump'],
            ['grupo' => 'Combustível', 'nome' => 'Posto Ipiranga',     'icone' => 'fa-gas-pump'],
            ['grupo' => 'Combustível', 'nome' => 'Posto BR / Vibra',   'icone' => 'fa-gas-pump'],
            ['grupo' => 'Combustível', 'nome' => 'Posto Raízen',       'icone' => 'fa-gas-pump'],
            ['grupo' => 'Combustível', 'nome' => 'Posto Ale',          'icone' => 'fa-gas-pump'],

            // ══════════════════════════════════════════════════════
            // 🍔 Alimentação e Delivery
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Alimentação', 'nome' => "McDonald's",         'icone' => 'fa-burger'],
            ['grupo' => 'Alimentação', 'nome' => 'Burger King',        'icone' => 'fa-burger'],
            ['grupo' => 'Alimentação', 'nome' => 'Subway',             'icone' => 'fa-sandwich'],
            ["grupo" => 'Alimentação', 'nome' => "Bob's",              'icone' => 'fa-ice-cream'],
            ['grupo' => 'Alimentação', 'nome' => 'KFC',                'icone' => 'fa-drumstick-bite'],
            ['grupo' => 'Alimentação', 'nome' => "Habib's",            'icone' => 'fa-utensils'],
            ['grupo' => 'Alimentação', 'nome' => 'Giraffas',           'icone' => 'fa-utensils'],
            ['grupo' => 'Alimentação', 'nome' => 'Pizza Hut',          'icone' => 'fa-pizza-slice'],
            ["grupo" => 'Alimentação', 'nome' => "Domino's",           'icone' => 'fa-pizza-slice'],
            ['grupo' => 'Alimentação', 'nome' => 'iFood',              'icone' => 'fa-motorcycle'],
            ['grupo' => 'Alimentação', 'nome' => 'Rappi',              'icone' => 'fa-motorcycle'],
            ['grupo' => 'Alimentação', 'nome' => 'Uber Eats',          'icone' => 'fa-motorcycle'],

            // ══════════════════════════════════════════════════════
            // 📺 Streaming e Assinaturas Digitais
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Streaming', 'nome' => 'Netflix',              'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Spotify',              'icone' => 'fa-music'],
            ['grupo' => 'Streaming', 'nome' => 'Amazon Prime',         'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Disney+',              'icone' => 'fa-star'],
            ['grupo' => 'Streaming', 'nome' => 'Globoplay',            'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Max (HBO)',            'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Apple TV+',            'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Paramount+',          'icone' => 'fa-tv'],
            ['grupo' => 'Streaming', 'nome' => 'Deezer',               'icone' => 'fa-music'],
            ['grupo' => 'Streaming', 'nome' => 'YouTube Premium',      'icone' => 'fa-play'],
            ['grupo' => 'Streaming', 'nome' => 'Xbox Game Pass',       'icone' => 'fa-gamepad'],
            ['grupo' => 'Streaming', 'nome' => 'PlayStation Plus',     'icone' => 'fa-gamepad'],

            // ══════════════════════════════════════════════════════
            // 📱 Telecom e Internet
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Telecom', 'nome' => 'Vivo',                   'icone' => 'fa-signal'],
            ['grupo' => 'Telecom', 'nome' => 'Claro',                  'icone' => 'fa-signal'],
            ['grupo' => 'Telecom', 'nome' => 'TIM',                    'icone' => 'fa-signal'],
            ['grupo' => 'Telecom', 'nome' => 'Oi',                     'icone' => 'fa-signal'],
            ['grupo' => 'Telecom', 'nome' => 'NET / Claro NET',        'icone' => 'fa-wifi'],
            ['grupo' => 'Telecom', 'nome' => 'Sky',                    'icone' => 'fa-satellite-dish'],
            ['grupo' => 'Telecom', 'nome' => 'Brisanet',               'icone' => 'fa-wifi'],

            // ══════════════════════════════════════════════════════
            // 🏦 Bancos e Finanças
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Bancos', 'nome' => 'Nubank',                  'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'Itaú',                    'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'Bradesco',                'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'Santander',               'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'Caixa Econômica Federal', 'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'Banco do Brasil',         'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'BTG Pactual',             'icone' => 'fa-chart-line'],
            ['grupo' => 'Bancos', 'nome' => 'XP Investimentos',        'icone' => 'fa-chart-line'],
            ['grupo' => 'Bancos', 'nome' => 'Banco Inter',             'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'C6 Bank',                 'icone' => 'fa-building-columns'],
            ['grupo' => 'Bancos', 'nome' => 'PicPay',                  'icone' => 'fa-mobile-screen'],
            ['grupo' => 'Bancos', 'nome' => 'Mercado Pago',            'icone' => 'fa-mobile-screen'],

            // ══════════════════════════════════════════════════════
            // 💡 Energia e Saneamento
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Energia e Água', 'nome' => 'Enel',            'icone' => 'fa-bolt'],
            ['grupo' => 'Energia e Água', 'nome' => 'CPFL Energia',    'icone' => 'fa-bolt'],
            ['grupo' => 'Energia e Água', 'nome' => 'Cemig',           'icone' => 'fa-bolt'],
            ['grupo' => 'Energia e Água', 'nome' => 'Light',           'icone' => 'fa-bolt'],
            ['grupo' => 'Energia e Água', 'nome' => 'Sabesp',          'icone' => 'fa-droplet'],
            ['grupo' => 'Energia e Água', 'nome' => 'Copasa',          'icone' => 'fa-droplet'],
            ['grupo' => 'Energia e Água', 'nome' => 'Sanepar',         'icone' => 'fa-droplet'],
            ['grupo' => 'Energia e Água', 'nome' => 'CEDAE',           'icone' => 'fa-droplet'],
            ['grupo' => 'Energia e Água', 'nome' => 'Comgás',          'icone' => 'fa-fire-flame-simple'],

            // ══════════════════════════════════════════════════════
            // 🏋️ Academia e Saúde
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Academia e Saúde', 'nome' => 'Smart Fit',     'icone' => 'fa-dumbbell'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Bodytech',      'icone' => 'fa-dumbbell'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Bio Ritmo',     'icone' => 'fa-dumbbell'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Bluefit',       'icone' => 'fa-dumbbell'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Unimed',        'icone' => 'fa-heart-pulse'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Amil',          'icone' => 'fa-heart-pulse'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'Hapvida',       'icone' => 'fa-heart-pulse'],
            ['grupo' => 'Academia e Saúde', 'nome' => 'SulAmérica Saúde', 'icone' => 'fa-heart-pulse'],

            // ══════════════════════════════════════════════════════
            // 👗 Moda e Vestuário
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Moda', 'nome' => 'Renner',                    'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'Riachuelo',                 'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'C&A',                       'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'Marisa',                    'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'Zara',                      'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'Hering',                    'icone' => 'fa-shirt'],
            ['grupo' => 'Moda', 'nome' => 'Shein',                     'icone' => 'fa-bag-shopping'],

            // ══════════════════════════════════════════════════════
            // 🖥️ Varejo e Eletro
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Casas Bahia',    'icone' => 'fa-store'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Magazine Luiza', 'icone' => 'fa-store'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Americanas',     'icone' => 'fa-store'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Ponto',          'icone' => 'fa-store'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Amazon',         'icone' => 'fa-box'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Mercado Livre',  'icone' => 'fa-box'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Shopee',         'icone' => 'fa-bag-shopping'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Kabum',          'icone' => 'fa-computer'],
            ['grupo' => 'Varejo e Eletro', 'nome' => 'Fast Shop',      'icone' => 'fa-store'],

            // ══════════════════════════════════════════════════════
            // 🛡️ Seguros
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Seguros', 'nome' => 'Porto Seguro',           'icone' => 'fa-shield-halved'],
            ['grupo' => 'Seguros', 'nome' => 'SulAmérica Seguros',     'icone' => 'fa-shield-halved'],
            ['grupo' => 'Seguros', 'nome' => 'Bradesco Seguros',       'icone' => 'fa-shield-halved'],
            ['grupo' => 'Seguros', 'nome' => 'Allianz',                'icone' => 'fa-shield-halved'],
            ['grupo' => 'Seguros', 'nome' => 'Tokio Marine',           'icone' => 'fa-shield-halved'],

            // ══════════════════════════════════════════════════════
            // 🚗 Transporte e Mobilidade
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Transporte', 'nome' => 'Uber',                'icone' => 'fa-car'],
            ['grupo' => 'Transporte', 'nome' => '99',                  'icone' => 'fa-car'],
            ['grupo' => 'Transporte', 'nome' => 'BlaBlaCar',           'icone' => 'fa-car'],
            ['grupo' => 'Transporte', 'nome' => 'Localiza',            'icone' => 'fa-car-side'],
            ['grupo' => 'Transporte', 'nome' => 'Movida',              'icone' => 'fa-car-side'],

            // ══════════════════════════════════════════════════════
            // 📚 Educação Online
            // ══════════════════════════════════════════════════════
            ['grupo' => 'Educação', 'nome' => 'Alura',                 'icone' => 'fa-graduation-cap'],
            ['grupo' => 'Educação', 'nome' => 'Udemy',                 'icone' => 'fa-graduation-cap'],
            ['grupo' => 'Educação', 'nome' => 'Coursera',              'icone' => 'fa-graduation-cap'],
            ['grupo' => 'Educação', 'nome' => 'Descomplica',           'icone' => 'fa-book-open'],
            ['grupo' => 'Educação', 'nome' => 'DIO',                   'icone' => 'fa-laptop-code'],
            ['grupo' => 'Educação', 'nome' => 'Rocketseat',            'icone' => 'fa-rocket'],
        ];
    }
}
