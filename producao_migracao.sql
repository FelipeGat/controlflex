-- ==============================================================
-- CONTROLEFLEX — Script de Migração para Produção
-- Banco de destino: inves783_controleflex (MySQL 5.7)
-- Gerado em: 2026-03-25
-- Objetivo: Adaptar o schema legado ao novo Laravel 12
-- ATENÇÃO: Faça backup antes de executar!
-- ==============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = '';

-- ─── Stored Procedures auxiliares (MySQL 5.7 não tem ADD COLUMN IF NOT EXISTS) ──

DROP PROCEDURE IF EXISTS _cf_add_col;
DROP PROCEDURE IF EXISTS _cf_drop_col;
DROP PROCEDURE IF EXISTS _cf_rename_col;
DROP PROCEDURE IF EXISTS _cf_col_is_varchar;

DELIMITER //

-- Adiciona coluna somente se não existir
CREATE PROCEDURE _cf_add_col(tbl VARCHAR(100), col VARCHAR(100), col_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @_s = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', col_def);
        PREPARE _st FROM @_s; EXECUTE _st; DEALLOCATE PREPARE _st;
    END IF;
END //

-- Remove coluna somente se existir
CREATE PROCEDURE _cf_drop_col(tbl VARCHAR(100), col VARCHAR(100))
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @_s = CONCAT('ALTER TABLE `', tbl, '` DROP COLUMN `', col, '`');
        PREPARE _st FROM @_s; EXECUTE _st; DEALLOCATE PREPARE _st;
    END IF;
END //

-- Renomeia coluna somente se o nome ANTIGO existir
CREATE PROCEDURE _cf_rename_col(tbl VARCHAR(100), old_col VARCHAR(100), new_col VARCHAR(100), col_def TEXT)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = old_col
    ) THEN
        SET @_s = CONCAT('ALTER TABLE `', tbl, '` CHANGE COLUMN `', old_col, '` `', new_col, '` ', col_def);
        PREPARE _st FROM @_s; EXECUTE _st; DEALLOCATE PREPARE _st;
    END IF;
END //

DELIMITER ;

-- ==============================================================
-- PASSO 1: Criar tabela `users` (padrão Laravel/Breeze)
-- ==============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`                bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name`              varchar(255) NOT NULL,
  `email`             varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password`          varchar(255) NOT NULL,
  `foto`              varchar(255) DEFAULT NULL,
  `remember_token`    varchar(100) DEFAULT NULL,
  `created_at`        timestamp NULL DEFAULT NULL,
  `updated_at`        timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popula users a partir de usuarios (INSERT IGNORE evita duplicatas por email)
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `foto`, `created_at`, `updated_at`)
SELECT `id`, `nome`, `email`, `senha`, `foto`, NOW(), NOW()
FROM `usuarios`;

-- ==============================================================
-- PASSO 2: Adicionar coluna user_id em todas as tabelas
-- ==============================================================

CALL _cf_add_col('familiares',    'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('categorias',    'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('fornecedores',  'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('bancos',        'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('despesas',      'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('receitas',      'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');
CALL _cf_add_col('investimentos', 'user_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`');

-- Copia usuario_id → user_id (tabelas que tem usuario_id diretamente)
UPDATE `familiares`    SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;
UPDATE `fornecedores`  SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;
UPDATE `bancos`        SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;
UPDATE `despesas`      SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;
UPDATE `receitas`      SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;
UPDATE `investimentos` SET `user_id` = `usuario_id` WHERE `user_id` IS NULL;

-- Para categorias: não tem usuario_id, apenas tenant_id.
-- Mapear tenant_id → id do primeiro usuário daquele tenant.
UPDATE `categorias` c
JOIN (
    SELECT `tenant_id`, MIN(`id`) AS primeiro_usuario
    FROM `usuarios`
    GROUP BY `tenant_id`
) u ON c.`tenant_id` = u.`tenant_id`
SET c.`user_id` = u.`primeiro_usuario`
WHERE c.`user_id` IS NULL;

-- ==============================================================
-- PASSO 3: Renomear colunas camelCase → snake_case em familiares
-- ==============================================================

CALL _cf_rename_col('familiares', 'limiteCartao', 'limite_cartao', 'DECIMAL(10,2) NULL DEFAULT NULL');
CALL _cf_rename_col('familiares', 'limiteCheque', 'limite_cheque', 'DECIMAL(10,2) NULL DEFAULT NULL');
CALL _cf_rename_col('familiares', 'alertaGastos', 'alerta_gastos', 'INT NULL DEFAULT NULL');

-- ==============================================================
-- PASSO 4: Corrigir categorias.tipo: lowercase → UPPERCASE
-- ==============================================================

-- Alterar enum para aceitar UPPERCASE (ou trocar para VARCHAR temporariamente)
ALTER TABLE `categorias`
  MODIFY COLUMN `tipo` VARCHAR(20) NOT NULL;

UPDATE `categorias` SET `tipo` = UPPER(`tipo`);

-- Restaurar como enum UPPERCASE
ALTER TABLE `categorias`
  MODIFY COLUMN `tipo` ENUM('RECEITA','DESPESA') NOT NULL;

-- ==============================================================
-- PASSO 5: Converter despesas — quem_comprou, onde_comprou, forma_pagamento
--          de VARCHAR (texto/id-como-string) para INT (FK real)
-- ==============================================================

-- 5a. Adicionar colunas INT temporárias (nome diferente para não conflitar)
CALL _cf_add_col('despesas', '_qc_int', 'INT UNSIGNED NULL DEFAULT NULL');
CALL _cf_add_col('despesas', '_oc_int', 'INT UNSIGNED NULL DEFAULT NULL');
CALL _cf_add_col('despesas', '_fp_int', 'INT UNSIGNED NULL DEFAULT NULL');

-- 5b. Preencher: quem_comprou e onde_comprou eram IDs armazenados como VARCHAR
--     forma_pagamento era texto livre ('PIX', 'Débito') — usar banco_id como FK real
UPDATE `despesas`
SET
  `_qc_int` = NULLIF(CAST(`quem_comprou`   AS UNSIGNED), 0),
  `_oc_int` = NULLIF(CAST(`onde_comprou`   AS UNSIGNED), 0),
  `_fp_int` = `banco_id`
WHERE `_qc_int` IS NULL OR `_oc_int` IS NULL OR `_fp_int` IS NULL;

-- 5c. Remover colunas VARCHAR antigas e promover as INT
CALL _cf_drop_col('despesas', 'quem_comprou');
CALL _cf_rename_col('despesas', '_qc_int', 'quem_comprou', 'INT UNSIGNED NULL DEFAULT NULL');

CALL _cf_drop_col('despesas', 'onde_comprou');
CALL _cf_rename_col('despesas', '_oc_int', 'onde_comprou', 'INT UNSIGNED NULL DEFAULT NULL');

CALL _cf_drop_col('despesas', 'forma_pagamento');
CALL _cf_rename_col('despesas', '_fp_int', 'forma_pagamento', 'INT UNSIGNED NULL DEFAULT NULL');

-- ==============================================================
-- PASSO 6: Converter receitas — quem_recebeu, forma_recebimento
-- ==============================================================

CALL _cf_add_col('receitas', '_qr_int', 'INT UNSIGNED NULL DEFAULT NULL');
CALL _cf_add_col('receitas', '_fr_int', 'INT UNSIGNED NULL DEFAULT NULL');

-- quem_recebeu era ID como VARCHAR; forma_recebimento era banco_id como VARCHAR ('4')
-- ou NULL. Usar banco_id como FK real.
UPDATE `receitas`
SET
  `_qr_int` = NULLIF(CAST(`quem_recebeu`     AS UNSIGNED), 0),
  `_fr_int` = `banco_id`
WHERE `_qr_int` IS NULL OR `_fr_int` IS NULL;

CALL _cf_drop_col('receitas', 'quem_recebeu');
CALL _cf_rename_col('receitas', '_qr_int', 'quem_recebeu', 'INT UNSIGNED NULL DEFAULT NULL');

CALL _cf_drop_col('receitas', 'forma_recebimento');
CALL _cf_rename_col('receitas', '_fr_int', 'forma_recebimento', 'INT UNSIGNED NULL DEFAULT NULL');

-- ==============================================================
-- PASSO 7: Adicionar created_at / updated_at (onde não existem)
-- ==============================================================

CALL _cf_add_col('familiares',   'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('familiares',   'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `familiares` SET `created_at` = NOW(), `updated_at` = NOW() WHERE `created_at` IS NULL;

CALL _cf_add_col('categorias',   'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('categorias',   'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `categorias` SET `created_at` = NOW(), `updated_at` = NOW() WHERE `created_at` IS NULL;

CALL _cf_add_col('fornecedores', 'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('fornecedores', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `fornecedores` SET `created_at` = NOW(), `updated_at` = NOW() WHERE `created_at` IS NULL;

CALL _cf_add_col('bancos',       'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('bancos',       'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `bancos` SET `created_at` = NOW(), `updated_at` = NOW() WHERE `created_at` IS NULL;

-- despesas, receitas, investimentos já têm criado_em — mapear para created_at
CALL _cf_add_col('despesas',      'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('despesas',      'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `despesas`      SET `created_at` = `criado_em`, `updated_at` = `criado_em` WHERE `created_at` IS NULL;

CALL _cf_add_col('receitas',      'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('receitas',      'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `receitas`      SET `created_at` = `criado_em`, `updated_at` = `criado_em` WHERE `created_at` IS NULL;

CALL _cf_add_col('investimentos', 'created_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('investimentos', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
UPDATE `investimentos` SET `created_at` = `criado_em`, `updated_at` = `criado_em` WHERE `created_at` IS NULL;

-- ==============================================================
-- PASSO 8: Garantir deleted_at em tabelas que ainda não têm
-- ==============================================================

CALL _cf_add_col('familiares',   'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('categorias',   'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('fornecedores', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL _cf_add_col('bancos',       'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
-- despesas, receitas, investimentos já têm deleted_at no schema legado

-- ==============================================================
-- PASSO 9: Tabelas auxiliares do Laravel (sessions, cache, jobs, etc.)
-- ==============================================================

CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            varchar(255) NOT NULL,
  `user_id`       bigint(20) unsigned DEFAULT NULL,
  `ip_address`    varchar(45) DEFAULT NULL,
  `user_agent`    text,
  `payload`       longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key`        varchar(255) NOT NULL,
  `value`      mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key`        varchar(255) NOT NULL,
  `owner`      varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue`        varchar(255) NOT NULL,
  `payload`      longtext NOT NULL,
  `attempts`     tinyint(3) unsigned NOT NULL,
  `reserved_at`  int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at`   int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id`             varchar(255) NOT NULL,
  `name`           varchar(255) NOT NULL,
  `total_jobs`     int(11) NOT NULL,
  `pending_jobs`   int(11) NOT NULL,
  `failed_jobs`    int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options`        mediumtext DEFAULT NULL,
  `cancelled_at`   int(11) DEFAULT NULL,
  `created_at`     int(11) NOT NULL,
  `finished_at`    int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id`         bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid`       varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue`      text NOT NULL,
  `payload`    longtext NOT NULL,
  `exception`  longtext NOT NULL,
  `failed_at`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email`      varchar(255) NOT NULL,
  `token`      varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de migrations do Laravel (para que php artisan migrate não recrie tudo)
CREATE TABLE IF NOT EXISTS `migrations` (
  `id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch`     int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrar as migrations como já executadas (evita que o artisan recrie as tabelas)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table',                         1),
('0001_01_01_000001_create_cache_table',                         1),
('0001_01_01_000002_create_jobs_table',                          1),
('2024_01_01_000010_create_familiares_table',                    1),
('2024_01_01_000020_create_categorias_table',                    1),
('2024_01_01_000030_create_fornecedores_table',                  1),
('2024_01_01_000040_create_bancos_table',                        1),
('2024_01_01_000050_create_despesas_table',                      1),
('2024_01_01_000060_create_receitas_table',                      1),
('2024_01_01_000070_create_investimentos_table',                 1),
('2026_03_25_000001_add_soft_deletes_to_financial_tables',       1),
('2026_03_25_000002_add_indexes_to_financial_tables',            1);

-- ==============================================================
-- PASSO 10: Limpar stored procedures auxiliares
-- ==============================================================

DROP PROCEDURE IF EXISTS _cf_add_col;
DROP PROCEDURE IF EXISTS _cf_drop_col;
DROP PROCEDURE IF EXISTS _cf_rename_col;

SET foreign_key_checks = 1;

-- ==============================================================
-- FIM — Verifique os dados com:
--   SELECT id, name, email FROM users;
--   SELECT id, user_id, nome FROM familiares LIMIT 5;
--   SELECT id, user_id, nome, tipo FROM categorias LIMIT 5;
--   SELECT id, user_id, quem_comprou, forma_pagamento, valor FROM despesas LIMIT 5;
-- ==============================================================
