-- ==============================================================
-- CONTROLEFLEX — Importação dos dados de produção para desenvolvimento
-- Origem : controleflex          (schema legado com dados reais)
-- Destino: controleflex_laravel  (novo schema Laravel 12)
-- Gerado em: 2026-03-25
--
-- ANTES DE EXECUTAR:
--   cd c:\xampp\htdocs\controleflex-laravel
--   php artisan migrate:fresh
--
-- Depois execute este script:
--   C:\xampp\mysql\bin\mysql -u root controleflex_laravel < importar_producao_local.sql
-- ==============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = '';

-- Limpar dados que o migrate:fresh possa ter deixado (seeds etc.)
TRUNCATE TABLE `users`;
TRUNCATE TABLE `familiares`;
TRUNCATE TABLE `categorias`;
TRUNCATE TABLE `fornecedores`;
TRUNCATE TABLE `bancos`;
TRUNCATE TABLE `despesas`;
TRUNCATE TABLE `receitas`;
TRUNCATE TABLE `investimentos`;

-- ==============================================================
-- 1. users  ←  controleflex.usuarios  (somente tenant_id = 1)
-- ==============================================================
-- Importa Felipe (id=1) e Jucilene (id=16) — mesma família (tenant=1).
-- A senha vem do campo `senha` (bcrypt do sistema antigo — funciona igual).

INSERT INTO `users` (`id`, `name`, `email`, `password`, `foto`, `created_at`, `updated_at`)
SELECT
    `id`,
    `nome`,
    `email`,
    `senha`,
    `foto`,
    NOW(),
    NOW()
FROM `controleflex`.`usuarios`
WHERE `tenant_id` = 1;

-- ==============================================================
-- 2. familiares  ←  controleflex.familiares (usuario_id = 1)
-- ==============================================================

INSERT INTO `familiares` (`id`, `user_id`, `nome`, `foto`, `salario`, `limite_cartao`, `limite_cheque`, `created_at`, `updated_at`)
SELECT
    `id`,
    `usuario_id`,                          -- user_id = usuario_id
    `nome`,
    `foto`,
    COALESCE(`salario`, 0),
    COALESCE(`limiteCartao`, 0),
    COALESCE(`limiteCheque`, 0),
    NOW(),
    NOW()
FROM `controleflex`.`familiares`
WHERE `usuario_id` = 1;

-- ==============================================================
-- 3. categorias  ←  controleflex.categorias (tenant_id = 1)
--    Atenção: tipo era lowercase ('receita','despesa') → UPPERCASE
--    Atenção: icones legados → classes FontAwesome 6
-- ==============================================================

INSERT INTO `categorias` (`id`, `user_id`, `nome`, `tipo`, `icone`, `created_at`, `updated_at`)
SELECT
    `id`,
    1,                                     -- todas as categorias do tenant 1 → user_id=1
    `nome`,
    UPPER(`tipo`),                         -- 'receita' → 'RECEITA', 'despesa' → 'DESPESA'
    CASE `icone`
        WHEN 'gifts'     THEN 'fa-gift'
        WHEN 'money'     THEN 'fa-money-bill'
        WHEN 'home'      THEN 'fa-house'
        WHEN 'food'      THEN 'fa-utensils'
        WHEN 'shop'      THEN 'fa-cart-shopping'
        WHEN 'education' THEN 'fa-graduation-cap'
        WHEN 'bills'     THEN 'fa-file-invoice-dollar'
        WHEN 'health'    THEN 'fa-heart-pulse'
        WHEN 'car'       THEN 'fa-car'
        ELSE 'fa-tag'
    END,
    NOW(),
    NOW()
FROM `controleflex`.`categorias`
WHERE `tenant_id` = 1;

-- ==============================================================
-- 4. fornecedores  ←  controleflex.fornecedores (usuario_id = 1)
-- ==============================================================

INSERT INTO `fornecedores` (`id`, `user_id`, `nome`, `telefone`, `cnpj`, `observacoes`, `created_at`, `updated_at`)
SELECT
    `id`,
    `usuario_id`,
    `nome`,
    `telefone`,
    `cnpj`,
    `observacoes`,
    NOW(),
    NOW()
FROM `controleflex`.`fornecedores`
WHERE `usuario_id` = 1;

-- ==============================================================
-- 5. bancos  ←  controleflex.bancos (usuario_id = 1)
-- ==============================================================

INSERT INTO `bancos` (`id`, `user_id`, `titular_id`, `nome`, `tipo_conta`, `codigo_banco`, `agencia`, `conta`,
                      `saldo`, `cheque_especial`, `saldo_cheque`, `limite_cartao`, `saldo_cartao`,
                      `created_at`, `updated_at`)
SELECT
    `id`,
    `usuario_id`,
    `titular_id`,
    `nome`,
    CASE `tipo_conta`
        WHEN 'Conta Corrente' THEN 'Conta Corrente'
        WHEN 'Poupança'       THEN 'Poupança'
        WHEN 'Dinheiro'       THEN 'Dinheiro'
        WHEN 'Cartão'         THEN 'Cartão de Crédito'
        ELSE 'Conta Corrente'
    END,
    `codigo_banco`,
    `agencia`,
    `conta`,
    COALESCE(`saldo`, 0),
    COALESCE(`cheque_especial`, 0),
    COALESCE(`saldo_cheque`, 0),
    COALESCE(`limite_cartao`, 0),
    COALESCE(`saldo_cartao`, 0),
    NOW(),
    NOW()
FROM `controleflex`.`bancos`
WHERE `usuario_id` = 1;

-- ==============================================================
-- 6. despesas  ←  controleflex.despesas (usuario_id = 1)
--
--    Mapeamentos:
--    quem_comprou  VARCHAR(id)  → INT  (era familiar_id como string)
--    onde_comprou  VARCHAR(id)  → INT  (era fornecedor_id como string)
--    forma_pagamento VARCHAR   → INT  (era texto 'PIX','Débito'; usar banco_id)
--    frequencia: valores antigos são compatíveis (mensal, semanal, etc.)
-- ==============================================================

INSERT INTO `despesas` (
    `id`, `user_id`, `quem_comprou`, `onde_comprou`, `categoria_id`, `forma_pagamento`,
    `valor`, `data_compra`, `data_pagamento`, `observacoes`,
    `recorrente`, `parcelas`, `frequencia`, `grupo_recorrencia_id`,
    `deleted_at`, `created_at`, `updated_at`
)
SELECT
    `id`,
    `usuario_id`,
    NULLIF(CAST(`quem_comprou` AS UNSIGNED), 0),   -- VARCHAR '19' → INT 19
    NULLIF(CAST(`onde_comprou` AS UNSIGNED), 0),   -- VARCHAR '6'  → INT 6
    `categoria_id`,
    `banco_id`,                                    -- forma_pagamento = banco_id (FK real)
    `valor`,
    `data_compra`,
    `data_pagamento`,
    `observacoes`,
    `recorrente`,
    COALESCE(`parcelas`, 1),
    CASE
        WHEN `frequencia` IN ('diaria','semanal','quinzenal','mensal','trimestral','semestral','anual')
        THEN `frequencia`
        ELSE 'mensal'
    END,
    `grupo_recorrencia_id`,
    `deleted_at`,
    `criado_em`,
    `criado_em`
FROM `controleflex`.`despesas`
WHERE `usuario_id` = 1;

-- ==============================================================
-- 7. receitas  ←  controleflex.receitas (usuario_id = 1)
--
--    quem_recebeu    VARCHAR(id) → INT  (era familiar_id como string)
--    forma_recebimento VARCHAR   → INT  (era banco_id como string ou NULL; usar banco_id)
-- ==============================================================

INSERT INTO `receitas` (
    `id`, `user_id`, `quem_recebeu`, `categoria_id`, `forma_recebimento`,
    `valor`, `data_prevista_recebimento`, `data_recebimento`, `observacoes`,
    `recorrente`, `parcelas`, `frequencia`, `grupo_recorrencia_id`,
    `deleted_at`, `created_at`, `updated_at`
)
SELECT
    `id`,
    `usuario_id`,
    NULLIF(CAST(`quem_recebeu` AS UNSIGNED), 0),   -- VARCHAR '19' → INT 19
    `categoria_id`,
    `banco_id`,                                    -- forma_recebimento = banco_id
    `valor`,
    `data_prevista_recebimento`,
    `data_recebimento`,
    `observacoes`,
    `recorrente`,
    COALESCE(`parcelas`, 1),
    CASE
        WHEN `frequencia` IN ('diaria','semanal','quinzenal','mensal','trimestral','semestral','anual')
        THEN `frequencia`
        ELSE 'mensal'
    END,
    `grupo_recorrencia_id`,
    `deleted_at`,
    `criado_em`,
    `criado_em`
FROM `controleflex`.`receitas`
WHERE `usuario_id` = 1;

-- ==============================================================
-- 8. investimentos  ←  controleflex.investimentos (usuario_id = 1)
-- ==============================================================

INSERT INTO `investimentos` (
    `id`, `user_id`, `banco_id`, `nome_ativo`, `tipo_investimento`,
    `data_aporte`, `valor_aportado`, `quantidade_cotas`, `observacoes`,
    `deleted_at`, `created_at`, `updated_at`
)
SELECT
    `id`,
    `usuario_id`,
    `banco_id`,
    `nome_ativo`,
    `tipo_investimento`,
    `data_aporte`,
    `valor_aportado`,
    COALESCE(`quantidade_cotas`, 0),
    `observacoes`,
    `deleted_at`,
    `criado_em`,
    `criado_em`
FROM `controleflex`.`investimentos`
WHERE `usuario_id` = 1;

-- ==============================================================
-- Reabilitar FK checks
-- ==============================================================

SET foreign_key_checks = 1;

-- ==============================================================
-- Verificação rápida após importação:
-- ==============================================================
SELECT 'users'        AS tabela, COUNT(*) AS registros FROM `users`
UNION ALL
SELECT 'familiares',   COUNT(*) FROM `familiares`
UNION ALL
SELECT 'categorias',   COUNT(*) FROM `categorias`
UNION ALL
SELECT 'fornecedores', COUNT(*) FROM `fornecedores`
UNION ALL
SELECT 'bancos',       COUNT(*) FROM `bancos`
UNION ALL
SELECT 'despesas',     COUNT(*) FROM `despesas`
UNION ALL
SELECT 'receitas',     COUNT(*) FROM `receitas`
UNION ALL
SELECT 'investimentos',COUNT(*) FROM `investimentos`;
