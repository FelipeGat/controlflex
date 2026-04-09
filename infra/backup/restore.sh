#!/bin/bash
# =============================================================================
# AlfaHome — Restaurar Backup Global
# Uso: restore.sh <date YYYY-MM-DD>
#
# Restaura o dump completo do banco + storage/ a partir do backup global.
# Para restaurar apenas um tenant específico, use restore_tenant.sh.
# =============================================================================
set -euo pipefail

DATE="${1:?Informe a data do backup (YYYY-MM-DD)}"

if ! echo "$DATE" | grep -qE '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'; then
  echo "Erro: data deve estar no formato YYYY-MM-DD"
  exit 1
fi

LOG_DIR="/opt/alfahome/backup/logs"
LOG_FILE="${LOG_DIR}/restore_${DATE}.log"
RUN_DIR="/opt/alfahome/backup/run"
TMP_DIR="/tmp/alfahome_restore_${DATE}_$$"
GDRIVE_REMOTE="gdrive:AlfaHome Backups"
DB_CONTAINER="alfa-home-db"
DB_NAME="alfahome"
PROJECT_DIR="/var/www/alfahome"

mkdir -p "$LOG_DIR" "$TMP_DIR" "$RUN_DIR"

exec > >(tee -a "$LOG_FILE") 2>&1

[ -f /opt/alfahome/backup/backup.env ] && source /opt/alfahome/backup/backup.env

DB_USER=$(docker exec "$DB_CONTAINER" printenv MYSQL_USER 2>/dev/null || echo "alfahome")
DB_PASS=$(docker exec "$DB_CONTAINER" printenv MYSQL_PASSWORD 2>/dev/null)

_status() {
  echo "{\"status\":\"$1\",\"message\":\"$2\",\"updated_at\":\"$(date -Iseconds)\"}" > "${RUN_DIR}/status.json"
}
_log() { echo "[$(date +%H:%M:%S)] $*"; }

_notify() {
  local MSG="$1"
  [ -z "${TELEGRAM_BOT_TOKEN:-}" ] && return 0
  curl -s "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
    --data-urlencode "chat_id=${TELEGRAM_CHAT_ID}" \
    --data-urlencode "text=${MSG}" > /dev/null 2>&1 || true
}
trap '_notify "❌ Restauração Global AlfaHome FALHOU (data: ${DATE}) — $(date +%H:%M:%S)"' ERR

_log "=== AlfaHome Restauração Global — data=${DATE} ==="
_status "running" "Restauração em andamento para ${DATE}..."
_notify "♻️ Restauração Global AlfaHome iniciada — data: ${DATE}"

# ─── 1. Baixar global do Google Drive ────────────────────────────────────────
_log "[1/3] Baixando backup global do Google Drive..."

if ! rclone lsd "${GDRIVE_REMOTE}/" 2>/dev/null | awk '{print $NF}' | grep -q "^${DATE}$"; then
  _log "  ✗ Backup da data ${DATE} não encontrado no Google Drive"
  _status "error" "Backup não encontrado para ${DATE}"
  rm -rf "$TMP_DIR"; exit 1
fi

rclone copy "${GDRIVE_REMOTE}/${DATE}/global/" "$TMP_DIR" 2>&1 | tail -3

SQL_GZ=$(ls "${TMP_DIR}"/alfahome_full_${DATE}.sql.gz 2>/dev/null | head -1 || true)
STORAGE_TAR=$(ls "${TMP_DIR}"/alfahome_storage_${DATE}.tar.gz 2>/dev/null | head -1 || true)

if [ -z "$SQL_GZ" ]; then
  _log "  ✗ Dump SQL global não encontrado para ${DATE}"
  _status "error" "Dump SQL não encontrado em ${DATE}/global/"
  rm -rf "$TMP_DIR"; exit 1
fi

_log "  ✓ SQL: $(basename "$SQL_GZ") ($(du -sh "$SQL_GZ" | cut -f1))"
[ -n "$STORAGE_TAR" ] && _log "  ✓ Storage: $(basename "$STORAGE_TAR") ($(du -sh "$STORAGE_TAR" | cut -f1))"

# ─── 2. Restaurar MySQL ──────────────────────────────────────────────────────
_log "[2/3] Restaurando banco de dados MySQL..."

docker exec -i "$DB_CONTAINER" mysql \
  -u "$DB_USER" -p"$DB_PASS" 2>/dev/null <<SQL
DROP DATABASE IF EXISTS \`${DB_NAME}\`;
CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

zcat "$SQL_GZ" | docker exec -i "$DB_CONTAINER" mysql \
  -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null

_log "  ✓ Banco restaurado com sucesso"

# ─── 3. Restaurar storage/ ──────────────────────────────────────────────────
_log "[3/3] Restaurando storage/ Laravel..."
if [ -n "$STORAGE_TAR" ]; then
  RESTORED=false

  if docker volume ls --format '{{.Name}}' | grep -qE "alfahome.*(app.storage|storage)"; then
    VOLUME_NAME=$(docker volume ls --format '{{.Name}}' | grep -E "alfahome.*(app.storage|storage)" | head -1)
    STORAGE_PATH="/var/lib/docker/volumes/${VOLUME_NAME}/_data"
    if [ -d "$STORAGE_PATH" ]; then
      tar -xzf "$STORAGE_TAR" -C "${STORAGE_PATH}" 2>/dev/null
      _log "  ✓ Storage restaurado (volume Docker)"
      RESTORED=true
    fi
  fi

  if [ "$RESTORED" = false ] && [ -d "${PROJECT_DIR}/storage" ]; then
    tar -xzf "$STORAGE_TAR" -C "${PROJECT_DIR}" 2>/dev/null
    _log "  ✓ Storage restaurado (diretório)"
    RESTORED=true
  fi

  [ "$RESTORED" = false ] && _log "  ⚠ Storage não restaurado — destino não localizado"
else
  _log "  — Sem backup de storage para restaurar"
fi

rm -rf "$TMP_DIR"

_log "=== Restauração global concluída com sucesso ==="
_status "success" "Restauração de ${DATE} concluída com sucesso."
_notify "✅ Restauração Global AlfaHome concluída — data: ${DATE}"

echo ""
echo "⚠️  Lembre-se de rodar após a restauração:"
echo "   docker exec alfa-home-app php artisan config:cache"
echo "   docker exec alfa-home-app php artisan route:cache"
echo "   docker exec alfa-home-app php artisan view:cache"
