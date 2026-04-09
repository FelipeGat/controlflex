# AlfaHome — Contexto do Projeto

Aplicação Laravel. Este arquivo documenta o fluxo obrigatório de changelog e notificações (Discord + Telegram) ao final de sessões de implementação.

---

## Fluxo de Trabalho Obrigatório

### Changelog ao final da sessão

Sempre que concluir implementações (feature, fix, refactor) em uma sessão, **obrigatoriamente**, ao final de todas as alterações:

1. Gerar/atualizar `CHANGELOG.md` na raiz do projeto. **Sempre iniciar com o nome do sistema (AlfaHome)** no título para identificar o produto em canais compartilhados.

   Formato:
   ```markdown
   ## AlfaHome — [DATA] — Título resumido

   ### Novidades
   - Descrição da feature voltada ao usuário final (sem termos técnicos)

   ### Melhorias
   - Descrição da melhoria perceptível ao usuário

   ### Correções
   - Descrição do bug corrigido

   ### Segurança
   - Descrição da melhoria de segurança (quando relevante comunicar)
   ```

2. Regras de conteúdo:
   - Linguagem simples, voltada ao cliente final (não ao desenvolvedor).
   - Não incluir detalhes de infraestrutura interna que não afetam o usuário.
   - Agrupar mudanças relacionadas em um único item.
   - Append no topo do arquivo (mais recente primeiro).

3. **Enviar para o Discord** automaticamente após gerar/atualizar o changelog, via webhook:

   ```bash
   curl -s -X POST "https://discord.com/api/webhooks/1491731306395074771/UokBgK-ZOiW4OIfn4J3eOMn5OoyP-WDVGmwooedQNK_-pxcKuI7rFJHwJ2Mjktu0ogZT" \
     -H "Content-Type: application/json" \
     --data '{
       "username": "AlfaHome Updates",
       "embeds": [{
         "title": "📋 AlfaHome — Changelog DD/MM/AAAA",
         "color": 16744192,
         "fields": [
           { "name": "🆕 Novidades", "value": "- item\n- item" },
           { "name": "⚡ Melhorias", "value": "- item" },
           { "name": "🔧 Correções", "value": "- item" },
           { "name": "🔒 Segurança", "value": "- item" }
         ],
         "footer": { "text": "AlfaHome • Alfa Soluções Tecnológicas" }
       }]
     }'
   ```

   - Cor do embed: laranja (`16744192`).
   - Username: `AlfaHome Updates`.
   - **Title sempre começando com "AlfaHome —"** (ex: `"📋 AlfaHome — Changelog 09/04/2026"`).
   - Footer: `AlfaHome • Alfa Soluções Tecnológicas`.
   - Incluir apenas as categorias que tiveram mudanças na sessão (omitir campos vazios).

4. **Enviar também para o Telegram** (grupo "Alfa Soluções Alertas") via Bot API:

   ```bash
   curl -s -X POST "https://api.telegram.org/bot8527552433:AAGKN3tQLO6EOfRLhKHU4FCHaCcdn9DlhbY/sendMessage" \
     --data-urlencode "chat_id=-5176787387" \
     --data-urlencode "parse_mode=HTML" \
     --data-urlencode "text=<mensagem HTML>"
   ```

   - Formato Telegram: HTML com `<b>` para títulos/destaques e `<i>` para itálico.
   - **Primeira linha sempre com nome do sistema**: `<b>📋 AlfaHome — Changelog DD/MM/AAAA</b>`.
   - Usar emojis por seção: 📋 (título), 🆕 (Novidades), ⚡ (Melhorias), 🔧 (Correções), 🔒 (Segurança).
   - Encerrar com `<i>AlfaHome • Alfa Soluções Tecnológicas</i>`.
   - Omitir seções que não tiveram mudanças.

### Testes obrigatórios antes de cada commit

Toda mudança de código (feature, fix, refactor) **deve ser validada com testes antes do commit**, sem exceção:

1. Antes de `git commit`, rodar a suíte de testes:
   ```bash
   docker exec alfa-app php artisan test
   # ou, fora do docker:
   php artisan test
   ```
2. Se a mudança tocar lógica nova (controller, service, model, regra de negócio), **escrever ou atualizar o teste correspondente** em `tests/Feature/` ou `tests/Unit/` antes do commit.
3. Mudanças puramente visuais (CSS, blade sem lógica) ainda exigem rodar a suíte para confirmar que nada quebrou — não precisam necessariamente de novo teste.
4. Commit só pode ser criado se **todos os testes passarem**. Se algum quebrar, corrigir antes — nunca commitar com teste vermelho.
5. Nunca usar `--no-verify` para pular hooks, nem `--force` para burlar testes.

### Identificação do sistema

Toda comunicação gerada pelo fluxo de changelog (arquivo, Discord, Telegram) **deve sempre conter o nome `AlfaHome`** no título/cabeçalho, pois os canais de Discord e Telegram são compartilhados entre os sistemas da Alfa Soluções Tecnológicas (AlfaGym, AlfaHome, etc).
