# AlfaHome — Guia de Primeiro Uso do Backup

## Pré-requisitos no servidor

1. Ter acesso SSH ao servidor: `ssh root@187.127.14.128`
2. O projeto deve estar em `/var/www/alfahome`

---

## Passo 1 — Atualizar o código no servidor

```bash
ssh root@187.127.14.128
cd /var/www/alfahome
git pull origin main
```

---

## Passo 2 — Executar o setup (uma única vez)

```bash
bash infra/backup/setup_gdrive.sh
```

Esse script irá:
- Instalar o `rclone` (se necessário)
- Criar os diretórios em `/opt/alfahome/backup/`
- **Autenticação Google Drive**: exibirá um link para abrir no navegador. Após autenticar, cole o código no terminal.
- Configurar os crons (backup diário às 03h BRT e listagem semanal)
- Criar `/opt/alfahome/backup/backup.env` para alertas Telegram

### Configurar alertas Telegram (opcional):
```bash
nano /opt/alfahome/backup/backup.env
# Preencha: TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID
```

---

## Passo 3 — Executar o primeiro backup manualmente

```bash
/opt/alfahome/backup/backup.sh
```

Acompanhe o progresso em tempo real:
```bash
tail -f /opt/alfahome/backup/logs/backup_$(date +%Y-%m-%d).log
```

---

## Comandos úteis

### Ver backups disponíveis no Google Drive:
```bash
/opt/alfahome/backup/list_backups.sh
cat /opt/alfahome/backup/run/backups.json
```

### Restaurar um backup por data:
```bash
/opt/alfahome/backup/restore.sh 2026-04-09
```

### Ver status do último backup:
```bash
cat /opt/alfahome/backup/run/status.json
```

### Ver logs:
```bash
tail -100 /opt/alfahome/backup/logs/backup_$(date +%Y-%m-%d).log
```

---

## Política de retenção automática (GFS)

| Período | Retenção |
|---------|----------|
| Últimos 7 dias | 1 backup por dia |
| 8–28 dias | 1 backup por semana |
| 29–90 dias | 1 backup por mês |
| > 90 dias | Removido automaticamente |
