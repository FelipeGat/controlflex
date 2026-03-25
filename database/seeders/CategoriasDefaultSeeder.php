<?php

namespace Database\Seeders;

use App\Models\Categoria;

class CategoriasDefaultSeeder
{
    /**
     * Insere as categorias padrão para um tenant.
     * Não duplica se já existirem categorias com o mesmo nome+tipo+tenant.
     */
    public static function seedParaTenant(int $tenantId, int $userId): void
    {
        foreach (self::categorias() as $cat) {
            $existe = Categoria::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('nome', $cat['nome'])
                ->where('tipo', $cat['tipo'])
                ->exists();

            if (!$existe) {
                Categoria::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId,
                    'user_id'   => $userId,
                    'nome'      => $cat['nome'],
                    'tipo'      => $cat['tipo'],
                    'icone'     => $cat['icone'],
                ]);
            }
        }
    }

    public static function categorias(): array
    {
        return [
            // ══════════════════════════════════════════════════════
            //  DESPESAS
            // ══════════════════════════════════════════════════════

            // 🏠 Moradia
            ['nome' => 'Aluguel / Financiamento',    'tipo' => 'DESPESA', 'icone' => 'fa-house'],
            ['nome' => 'Condomínio',                  'tipo' => 'DESPESA', 'icone' => 'fa-building'],
            ['nome' => 'IPTU',                        'tipo' => 'DESPESA', 'icone' => 'fa-file-invoice-dollar'],
            ['nome' => 'Energia Elétrica',            'tipo' => 'DESPESA', 'icone' => 'fa-bolt'],
            ['nome' => 'Água e Esgoto',               'tipo' => 'DESPESA', 'icone' => 'fa-droplet'],
            ['nome' => 'Gás',                         'tipo' => 'DESPESA', 'icone' => 'fa-fire-flame-simple'],
            ['nome' => 'Internet e TV',               'tipo' => 'DESPESA', 'icone' => 'fa-wifi'],
            ['nome' => 'Telefone / Celular',          'tipo' => 'DESPESA', 'icone' => 'fa-mobile-screen'],
            ['nome' => 'Reforma e Manutenção',        'tipo' => 'DESPESA', 'icone' => 'fa-screwdriver-wrench'],

            // 🛒 Alimentação
            ['nome' => 'Supermercado / Feira',        'tipo' => 'DESPESA', 'icone' => 'fa-cart-shopping'],
            ['nome' => 'Restaurante / Delivery',      'tipo' => 'DESPESA', 'icone' => 'fa-utensils'],
            ['nome' => 'Padaria e Café',              'tipo' => 'DESPESA', 'icone' => 'fa-mug-hot'],
            ['nome' => 'Lanchonete / Fast Food',      'tipo' => 'DESPESA', 'icone' => 'fa-burger'],

            // 🚗 Transporte
            ['nome' => 'Combustível',                 'tipo' => 'DESPESA', 'icone' => 'fa-gas-pump'],
            ['nome' => 'Transporte Público',          'tipo' => 'DESPESA', 'icone' => 'fa-bus'],
            ['nome' => 'Uber / 99 / Táxi',            'tipo' => 'DESPESA', 'icone' => 'fa-car'],
            ['nome' => 'Manutenção do Carro',         'tipo' => 'DESPESA', 'icone' => 'fa-car-burst'],
            ['nome' => 'IPVA / Licenciamento',        'tipo' => 'DESPESA', 'icone' => 'fa-file-invoice'],
            ['nome' => 'Pedágio / Estacionamento',    'tipo' => 'DESPESA', 'icone' => 'fa-road'],

            // 💊 Saúde
            ['nome' => 'Plano de Saúde',              'tipo' => 'DESPESA', 'icone' => 'fa-heart-pulse'],
            ['nome' => 'Farmácia / Remédios',         'tipo' => 'DESPESA', 'icone' => 'fa-pills'],
            ['nome' => 'Consulta Médica',             'tipo' => 'DESPESA', 'icone' => 'fa-stethoscope'],
            ['nome' => 'Dentista',                    'tipo' => 'DESPESA', 'icone' => 'fa-tooth'],
            ['nome' => 'Academia / Esportes',         'tipo' => 'DESPESA', 'icone' => 'fa-dumbbell'],
            ['nome' => 'Saúde Mental / Psicólogo',   'tipo' => 'DESPESA', 'icone' => 'fa-brain'],

            // 📚 Educação
            ['nome' => 'Escola / Faculdade',          'tipo' => 'DESPESA', 'icone' => 'fa-graduation-cap'],
            ['nome' => 'Cursos e Treinamentos',       'tipo' => 'DESPESA', 'icone' => 'fa-book-open'],
            ['nome' => 'Material Escolar',            'tipo' => 'DESPESA', 'icone' => 'fa-pencil'],
            ['nome' => 'Livros',                      'tipo' => 'DESPESA', 'icone' => 'fa-book'],

            // 👕 Roupas e Beleza
            ['nome' => 'Roupas / Calçados',           'tipo' => 'DESPESA', 'icone' => 'fa-shirt'],
            ['nome' => 'Cabeleireiro / Salão',        'tipo' => 'DESPESA', 'icone' => 'fa-scissors'],
            ['nome' => 'Produtos de Higiene',         'tipo' => 'DESPESA', 'icone' => 'fa-soap'],
            ['nome' => 'Cosméticos / Perfumes',       'tipo' => 'DESPESA', 'icone' => 'fa-spray-can-sparkles'],

            // 🎉 Lazer e Entretenimento
            ['nome' => 'Streaming (Netflix, Spotify)', 'tipo' => 'DESPESA', 'icone' => 'fa-tv'],
            ['nome' => 'Cinema / Teatro / Show',      'tipo' => 'DESPESA', 'icone' => 'fa-film'],
            ['nome' => 'Viagens / Turismo',           'tipo' => 'DESPESA', 'icone' => 'fa-plane'],
            ['nome' => 'Bares e Restaurantes',        'tipo' => 'DESPESA', 'icone' => 'fa-martini-glass-citrus'],
            ['nome' => 'Jogos / Hobbies',             'tipo' => 'DESPESA', 'icone' => 'fa-gamepad'],

            // 👶 Filhos
            ['nome' => 'Creche / Escola Infantil',    'tipo' => 'DESPESA', 'icone' => 'fa-child'],
            ['nome' => 'Brinquedos / Atividades',     'tipo' => 'DESPESA', 'icone' => 'fa-puzzle-piece'],

            // 🐾 Pets
            ['nome' => 'Vet / Pet Shop',              'tipo' => 'DESPESA', 'icone' => 'fa-paw'],
            ['nome' => 'Ração e Acessórios',          'tipo' => 'DESPESA', 'icone' => 'fa-bone'],

            // 🏦 Serviços e Finanças
            ['nome' => 'Tarifas Bancárias',           'tipo' => 'DESPESA', 'icone' => 'fa-building-columns'],
            ['nome' => 'Seguros',                     'tipo' => 'DESPESA', 'icone' => 'fa-shield-halved'],
            ['nome' => 'Empréstimos / Dívidas',       'tipo' => 'DESPESA', 'icone' => 'fa-hand-holding-dollar'],
            ['nome' => 'Cartão de Crédito',           'tipo' => 'DESPESA', 'icone' => 'fa-credit-card'],
            ['nome' => 'Previdência Privada',         'tipo' => 'DESPESA', 'icone' => 'fa-umbrella'],
            ['nome' => 'Poupança / Reserva',          'tipo' => 'DESPESA', 'icone' => 'fa-piggy-bank'],

            // 🧾 Impostos e Taxas
            ['nome' => 'Imposto de Renda',            'tipo' => 'DESPESA', 'icone' => 'fa-receipt'],
            ['nome' => 'MEI / Simples Nacional',      'tipo' => 'DESPESA', 'icone' => 'fa-store'],
            ['nome' => 'Contador / Advogado',         'tipo' => 'DESPESA', 'icone' => 'fa-scale-balanced'],

            // 🎁 Outros
            ['nome' => 'Presentes',                   'tipo' => 'DESPESA', 'icone' => 'fa-gift'],
            ['nome' => 'Doações / Caridade',          'tipo' => 'DESPESA', 'icone' => 'fa-hand-holding-heart'],
            ['nome' => 'Serviços Domésticos',         'tipo' => 'DESPESA', 'icone' => 'fa-broom'],
            ['nome' => 'Outros',                      'tipo' => 'DESPESA', 'icone' => 'fa-ellipsis'],

            // ══════════════════════════════════════════════════════
            //  RECEITAS
            // ══════════════════════════════════════════════════════

            // 💼 Trabalho CLT
            ['nome' => 'Salário',                     'tipo' => 'RECEITA', 'icone' => 'fa-briefcase'],
            ['nome' => '13º Salário',                 'tipo' => 'RECEITA', 'icone' => 'fa-gift'],
            ['nome' => 'Férias',                      'tipo' => 'RECEITA', 'icone' => 'fa-umbrella-beach'],
            ['nome' => 'Hora Extra / Adicional',      'tipo' => 'RECEITA', 'icone' => 'fa-clock'],
            ['nome' => 'Bônus / PLR',                 'tipo' => 'RECEITA', 'icone' => 'fa-star'],

            // 💻 Autônomo / MEI
            ['nome' => 'Freelance / Bico',            'tipo' => 'RECEITA', 'icone' => 'fa-laptop'],
            ['nome' => 'Pró-labore',                  'tipo' => 'RECEITA', 'icone' => 'fa-user-tie'],
            ['nome' => 'Lucro Empresa',               'tipo' => 'RECEITA', 'icone' => 'fa-chart-line'],
            ['nome' => 'Venda de Produtos',           'tipo' => 'RECEITA', 'icone' => 'fa-store'],
            ['nome' => 'Prestação de Serviços',       'tipo' => 'RECEITA', 'icone' => 'fa-handshake'],

            // 📈 Investimentos
            ['nome' => 'Rendimento CDB / Tesouro',   'tipo' => 'RECEITA', 'icone' => 'fa-landmark'],
            ['nome' => 'Dividendos / FIIs',           'tipo' => 'RECEITA', 'icone' => 'fa-chart-pie'],
            ['nome' => 'Rendimento Poupança',         'tipo' => 'RECEITA', 'icone' => 'fa-piggy-bank'],
            ['nome' => 'Venda de Ações / Cripto',     'tipo' => 'RECEITA', 'icone' => 'fa-arrow-trend-up'],
            ['nome' => 'Aluguel de Imóvel',           'tipo' => 'RECEITA', 'icone' => 'fa-house-chimney'],

            // 🏛️ Benefícios e Governo
            ['nome' => 'Vale Alimentação / Refeição', 'tipo' => 'RECEITA', 'icone' => 'fa-utensils'],
            ['nome' => 'Vale Transporte',             'tipo' => 'RECEITA', 'icone' => 'fa-bus'],
            ['nome' => 'FGTS',                        'tipo' => 'RECEITA', 'icone' => 'fa-building-columns'],
            ['nome' => 'Bolsa Família / Auxílio Gov.','tipo' => 'RECEITA', 'icone' => 'fa-hand-holding-dollar'],
            ['nome' => 'Aposentadoria / Pensão',      'tipo' => 'RECEITA', 'icone' => 'fa-person-cane'],
            ['nome' => 'Restituição IR',              'tipo' => 'RECEITA', 'icone' => 'fa-receipt'],

            // 🎲 Eventuais
            ['nome' => 'Venda de Bens / Objetos',    'tipo' => 'RECEITA', 'icone' => 'fa-tags'],
            ['nome' => 'Reembolso',                   'tipo' => 'RECEITA', 'icone' => 'fa-rotate-left'],
            ['nome' => 'Herança / Doação Recebida',  'tipo' => 'RECEITA', 'icone' => 'fa-envelope-open-text'],
            ['nome' => 'Prêmio / Sorteio',            'tipo' => 'RECEITA', 'icone' => 'fa-trophy'],
            ['nome' => 'Pensão Alimentícia',          'tipo' => 'RECEITA', 'icone' => 'fa-children'],
            ['nome' => 'Outros',                      'tipo' => 'RECEITA', 'icone' => 'fa-ellipsis'],
        ];
    }
}
