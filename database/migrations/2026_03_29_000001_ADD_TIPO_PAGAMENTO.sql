-- Executar este script caso não seja possível rodar o artisan migrate
-- Adiciona coluna tipo_pagamento em despesas e receitas

ALTER TABLE `despesas`
  ADD COLUMN `tipo_pagamento` VARCHAR(20) NULL DEFAULT NULL AFTER `forma_pagamento`;

ALTER TABLE `receitas`
  ADD COLUMN `tipo_pagamento` VARCHAR(20) NULL DEFAULT NULL AFTER `forma_recebimento`;
