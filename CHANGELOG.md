# Changelog

## AlfaHome — 10/04/2026 — Landing page de apresentação

### Novidades
- Nova landing page completa em `public/landing/index.html` com design moderno e responsivo
- Apresentação visual dos planos (Individual, Casal, Família) com preços e benefícios
- Mockups CSS interativos mostrando funcionalidades de controle de despesas e investimentos
- Demonstração dos KPIs principais: Saldo Total, Despesas, Receitas e Investimentos
- Seção de funcionalidades com 8 cards destacando principais recursos do sistema

### Melhorias
- Interface com tema claro e escuro (toggle disponível na navbar)
- Animações suaves ao descer a página (scroll reveal)
- Design responsivo para desktop, tablet e mobile
- FAQ interativa com 5 perguntas frequentes
- Integração direta com WhatsApp para suporte e vendas
- Formulário de contato que envia mensagens via WhatsApp
### Correções
- Adicionada rota pública `/landing` no Laravel para servir a landing page
- Assets (favicon, logo) agora acessíveis em `/public`

### Correções
- Corrigido endpoint de verificação de manutenção em tempo real (`/status-manutencao`) que não estava sendo acessível por usuários logados devido a cache interno do servidor

## AlfaHome — 09/04/2026 — Correção no aviso de manutenção agendada

### Correções
- O aviso de manutenção programada (banner laranja no topo) agora é exibido para **todos os usuários** quando há uma manutenção agendada, e não apenas para o administrador

## AlfaHome — 09/04/2026 — Melhorias no modo manutenção

### Melhorias
- Quando o sistema entra em manutenção, os usuários são redirecionados diretamente para a tela de login com um aviso em destaque, em vez de uma tela separada de manutenção
- O aviso na tela de login informa o título e a mensagem configurados pelo administrador

## AlfaHome — 09/04/2026 — Correções no sistema de manutenção e fuso horário

### Correções
- Corrigido problema em que o modo manutenção não ativava corretamente: o sistema usava horário UTC internamente, mas os horários digitados pelo usuário são no fuso de Brasília (BRT). Agora o sistema usa o fuso horário correto (America/Sao_Paulo)
- Adicionado alerta visual no painel de manutenção quando o modo está ativado mas a janela de tempo já expirou, orientando o administrador a redefinir as datas
- Melhorada a validação do formulário: o campo "Fim" agora rejeita datas já passadas, evitando configurações que nunca seriam ativadas
- Corrigidos erros de carregamento de gráficos (Chart.js) e do sistema de instalação como app (PWA) causados pelas novas regras de segurança CSP

## AlfaHome — 09/04/2026 — Correção de compatibilidade do CSP

### Correções
- Resolvido problema que impedia o carregamento correto de gráficos e do sistema de instalação como app (PWA) após a ativação das regras de segurança CSP
- Corrigidos erros no console do browser relacionados ao Chart.js, Alpine.js e beacon do Cloudflare

## AlfaHome — 09/04/2026 — Sistema de manutenção programada

### Novidades
- Novo sistema de manutenção programada: o administrador pode colocar o sistema em manutenção a qualquer momento, com título e mensagem personalizados
- Suporte a agendamento: é possível definir data e hora de início e fim da manutenção — o sistema ativa e desativa automaticamente na janela configurada
- Contador regressivo: usuários veem o tempo restante até o fim da manutenção em tempo real
- Durante a manutenção, o sistema desconecta automaticamente todos os usuários e exibe uma tela informativa
- Administrador (super_admin) continua acessando o sistema normalmente e vê um aviso em destaque enquanto a manutenção está ativa

## AlfaHome — 09/04/2026 — Melhorias de segurança

### Segurança
- Proteção contra ataques de falsificação de origem de rede (IP spoofing): o sistema agora valida corretamente a origem das requisições, impedindo que atacantes simulem endereços confiáveis
- Ativação do HSTS (HTTP Strict Transport Security): browsers são instruídos a usar sempre HTTPS, bloqueando ataques de interceptação SSL
- Adição de Content-Security-Policy (CSP): o browser agora bloqueia automaticamente scripts e recursos de origens não autorizadas, reduzindo a superfície de ataques XSS
- Remoção de permissão CORS excessiva em arquivos de usuário: arquivos financeiros (extratos, comprovantes) não são mais acessíveis por domínios externos
- Correção no arquivo de configuração de exemplo: debug desativado por padrão para evitar exposição acidental de informações sensíveis em novos deploys

## AlfaHome — 09/04/2026 — Sistema de backup automático

### Novidades
- Backup diário automático dos dados do sistema, realizado às 03h (horário de Brasília). Os dados são armazenados com segurança no Google Drive.
- Possibilidade de restaurar o sistema a qualquer momento a partir de um backup anterior, por data.

### Melhorias
- Retenção inteligente de backups: diária por 7 dias, semanal por 4 semanas e mensal por 3 meses — sem acúmulo desnecessário de espaço.
- Notificação via Telegram ao concluir ou falhar o backup.

---

## AlfaHome — 09/04/2026 — Desempenho e estabilidade

### Melhorias
- Sistema agora usa um mecanismo de cache mais rápido para guardar sessões e dados temporários, tornando o carregamento das telas mais ágil após o login.
- Infraestrutura do servidor foi ajustada para reduzir o tempo de resposta de páginas internas.

### Correções
- Resolvido um problema que podia causar instabilidade após atualizações do sistema no servidor (a tela inicial podia ficar indisponível por alguns instantes).

## AlfaHome — 09/04/2026 — Acesso ao sistema e melhorias de navegação

### Correções
- Resolvido um problema que impedia o acesso ao sistema em alguns momentos (erro ao carregar a tela de login).
- Botão de recolher a barra lateral agora responde de forma mais consistente, sem duplo clique acidental.

### Melhorias
- Ícone do aviso de "Instalar AlfaHome" atualizado para melhor reconhecimento visual.
