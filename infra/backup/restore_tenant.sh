#!/bin/bash
# =============================================================================
# AlfaHome — Restaurar Backup de um Tenant Específico
# Uso: restore_tenant.sh <tenant_id> <date YYYY-MM-DD>
#
# Baixa o backup individual do tenant no Google Drive e restaura apenas
# os dados daquele tenant (DELETE + reimport), sem afetar os demais.
# =============================================================================
set -euo pipefail

TENANT_ID="${1:?Informe o tenant_id}"
DATE="${2:?Informe a data do backup (YYYY-MM-DD)}"

if ! echo "$DATE" | grep -qE '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'; then
  echo "Erro: data deve estar no formato YYYY-MM-DD"
  exit 1
fi

LOG_DIR="/opt/alfahome/backup/logs"
LOG_FILE="${LOG_DIR}/restore_tenant_${TENANT_ID}_${DATE}.log"
RUN_DIR="/opt/alfahome/backup/run"
TMP_DIR="/tmp/alfahome_restore_tenant_${TENANT_ID}_${DATE}_$$"
GDRIVE_REMOTE="gdrive:AlfaHome Backups"
DB_CONTAINER="alfa-home-db"
DB_NAME="alfahome"

mkdir -p "$LOG_DIR" "$TMP_DIR" "$RUN_DIR"

INICIO=$(date +%s)

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
trap '_notify "❌ Restauração AlfaHome falhou (tenant ${TENANT_ID:-?}) — $(date +%H:%M:%S)"' ERR

_log "=== Restauração tenant_id=${TENANT_ID} data=${DATE} ==="
_status "running" "Restauração em andamento para tenant ${TENANT_ID}..."

# ─── Descobre nome do tenant ──────────────────────────────────────────────────
TNOME=$(docker exec "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null \
  -N -e "SELECT REGEXP_REPLACE(UPPER(nome), '[^A-Z0-9]', '_') FROM tenants WHERE id=${TENANT_ID};" | head -1)

if [ -z "$TNOME" ]; then
  _notify "❌ Restauração AlfaHome: tenant ${TENANT_ID} não encontrado"
  _status "error" "Tenant ${TENANT_ID} não encontrado"
  rm -rf "$TMP_DIR"; exit 1
fi

_notify "♻️ Restauração AlfaHome iniciada — tenant ${TENANT_ID} (${TNOME}), data: ${DATE}"

# ─── 1. Baixar do Google Drive ────────────────────────────────────────────────
_log "[1/3] Baixando backup do Google Drive..."

# Pasta: AlfaHome Backups/<date>/clientes/<TNOME>/
GDRIVE_PATH="${GDRIVE_REMOTE}/${DATE}/clientes/${TNOME}"

if ! rclone lsd "${GDRIVE_REMOTE}/${DATE}/clientes/" 2>/dev/null | awk '{print $NF}' | grep -q "^${TNOME}$"; then
  _log "  ✗ Pasta do tenant '${TNOME}' não encontrada em ${DATE}/clientes/"
  _status "error" "Backup não encontrado para tenant ${TENANT_ID} em ${DATE}"
  rm -rf "$TMP_DIR"; exit 1
fi

rclone copy "${GDRIVE_PATH}/" "$TMP_DIR" 2>&1 | tail -3

SQL_GZ=$(ls "${TMP_DIR}"/tenant_${TENANT_ID}_*_${DATE}.sql.gz 2>/dev/null | head -1 || true)

if [ -z "$SQL_GZ" ]; then
  _log "  ✗ Dump SQL não encontrado para tenant ${TENANT_ID} na data ${DATE}"
  _status "error" "Dump SQL não encontrado"
  rm -rf "$TMP_DIR"; exit 1
fi

_log "  ✓ SQL: $(basename "$SQL_GZ") ($(du -sh "$SQL_GZ" | cut -f1))"

# ─── 2. Restaurar MySQL (apenas dados do tenant) ──────────────────────────────
_log "[2/3] Restaurando dados MySQL do tenant ${TENANT_ID} (${TNOME})..."

# Tabelas com tenant_id
TABLES=$(docker exec "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null \
  -N -e "SELECT TABLE_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA='${DB_NAME}' AND COLUMN_NAME='tenant_id'
         ORDER BY TABLE_NAME;")

# DELETE dos dados atuais deste tenant + reimport
RESTORE_SQL="${TMP_DIR}/restore_tenant_${TENANT_ID}.sql"
{
  echo "SET FOREIGN_KEY_CHECKS=0;"

  # Remove dados existentes do tenant
  while IFS= read -r TABLE; do
    [ -z "$TABLE" ] && continue
    if [ "$TABLE" = "tenants" ]; then
      echo "DELETE FROM \`${TABLE}\` WHERE id = ${TENANT_ID} OR tenant_id = ${TENANT_ID};"
    else
      echo "DELETE FROM \`${TABLE}\` WHERE tenant_id = ${TENANT_ID};"
    fi
  done <<< "$TABLES"

  echo ""
  # Reimporta os dados do backup
  zcat "$SQL_GZ"

  echo "SET FOREIGN_KEY_CHECKS=1;"
} > "$RESTORE_SQL"

docker exec -i "$DB_CONTAINER" mysql \
  -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null < "$RESTORE_SQL"

_log "  ✓ Dados MySQL do tenant ${TENANT_ID} restaurados"

# ─── 3. Limpar ───────────────────────────────────────────────────────────────
_log "[3/3] Limpeza..."
rm -rf "$TMP_DIR"

DURACAO=$(( $(date +%s) - INICIO ))
[ "$DURACAO" -ge 60 ] && DUR_FMT="$((DURACAO/60))m $((DURACAO%60))s" || DUR_FMT="${DURACAO}s"

_log "=== Restauração do tenant concluída com sucesso ==="
_status "success" "Tenant ${TENANT_ID} restaurado com sucesso a partir de ${DATE}."
_notify "✅ Restauração AlfaHome concluída — tenant ${TENANT_ID} (${TNOME}), data: ${DATE}, ⏱ ${DUR_FMT}"
