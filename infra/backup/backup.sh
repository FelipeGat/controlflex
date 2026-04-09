#!/bin/bash
# =============================================================================
# AlfaHome — Backup Script
# Cria backup completo (MySQL + storage + configs) e envia ao Google Drive.
# Executado pelo cron diariamente às 03h (horário de Brasília / 06:00 UTC).
# =============================================================================
set -euo pipefail

DATE=$(TZ=America/Sao_Paulo date +%Y-%m-%d)
TMP_DIR="/tmp/alfahome_backup_${DATE}_$$"
LOG_DIR="/opt/alfahome/backup/logs"
LOG_FILE="${LOG_DIR}/backup_${DATE}.log"
RUN_DIR="/opt/alfahome/backup/run"
GDRIVE_REMOTE="gdrive:AlfaHome Backups"
DB_CONTAINER="alfa-home-db"
DB_NAME="alfahome"
# Diretório do projeto no servidor (ajuste se necessário)
PROJECT_DIR="/var/www/alfahome"

mkdir -p "$LOG_DIR" "$TMP_DIR" "$RUN_DIR"

exec > >(tee -a "$LOG_FILE") 2>&1

# Carrega configurações opcionais (alertas Telegram etc.)
[ -f /opt/alfahome/backup/backup.env ] && source /opt/alfahome/backup/backup.env

# Credenciais lidas do próprio container (não hardcoded)
DB_USER=$(docker exec "$DB_CONTAINER" printenv MYSQL_USER 2>/dev/null || echo "alfahome")
DB_PASS=$(docker exec "$DB_CONTAINER" printenv MYSQL_PASSWORD 2>/dev/null)

_status() {
  echo "{\"status\":\"$1\",\"message\":\"$2\",\"updated_at\":\"$(date -Iseconds)\"}" > "${RUN_DIR}/status.json"
}
_log() { echo "[$(date +%H:%M:%S)] $*"; }

INICIO=$(date +%s)
SQL_SIZE=""

_notify() {
  local MSG="$1"
  [ -z "${TELEGRAM_BOT_TOKEN:-}" ] && return 0
  curl -s "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
    --data-urlencode "chat_id=${TELEGRAM_CHAT_ID}" \
    --data-urlencode "text=${MSG}" > /dev/null 2>&1 || true
}
trap '_notify "❌ Backup AlfaHome falhou — $(date +%H:%M:%S)"' ERR

# Política GFS: diário 7d / semanal 4 semanas / mensal 3 meses
_gfs_cleanup() {
  local DAILY_CUT WEEKLY_CUT MONTHLY_CUT SEEN_WEEKS SEEN_MONTHS
  DAILY_CUT=$(date -d "-7 days" +%Y-%m-%d)
  WEEKLY_CUT=$(date -d "-28 days" +%Y-%m-%d)
  MONTHLY_CUT=$(date -d "-90 days" +%Y-%m-%d)
  SEEN_WEEKS=""
  SEEN_MONTHS=""

  while IFS= read -r D; do
    [[ -z "$D" ]] && continue

    if [[ "$D" > "$DAILY_CUT" || "$D" == "$DAILY_CUT" ]]; then
      continue
    fi

    if [[ "$D" > "$WEEKLY_CUT" || "$D" == "$WEEKLY_CUT" ]]; then
      WEEK=$(date -d "$D" +%G-W%V)
      if echo "$SEEN_WEEKS" | grep -qw "$WEEK"; then
        _log "  GFS semanal — removendo: $D"
        rclone purge "${GDRIVE_REMOTE}/${D}/" 2>/dev/null || true
      else
        SEEN_WEEKS="$SEEN_WEEKS $WEEK"
      fi
      continue
    fi

    if [[ "$D" > "$MONTHLY_CUT" || "$D" == "$MONTHLY_CUT" ]]; then
      MONTH=$(echo "$D" | cut -c1-7)
      if echo "$SEEN_MONTHS" | grep -qw "$MONTH"; then
        _log "  GFS mensal — removendo: $D"
        rclone purge "${GDRIVE_REMOTE}/${D}/" 2>/dev/null || true
      else
        SEEN_MONTHS="$SEEN_MONTHS $MONTH"
      fi
      continue
    fi

    _log "  GFS expirado — removendo: $D"
    rclone purge "${GDRIVE_REMOTE}/${D}/" 2>/dev/null || true

  done < <(rclone lsd "${GDRIVE_REMOTE}/" 2>/dev/null | awk '{print $NF}' \
    | grep -E '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' | sort -r)
}

_log "=== AlfaHome Backup iniciado ==="
_status "running" "Backup em andamento..."

# ─── 1. DUMP MYSQL ────────────────────────────────────────────────────────────
_log "[1/4] Dump MySQL..."
docker exec "$DB_CONTAINER" mysqldump \
  -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction --routines --triggers --add-drop-table \
  "$DB_NAME" 2>/dev/null \
  | gzip > "${TMP_DIR}/alfahome_${DATE}.sql.gz"

if ! gzip -t "${TMP_DIR}/alfahome_${DATE}.sql.gz" 2>/dev/null; then
  _notify "❌ Backup AlfaHome: dump SQL corrompido — $(date +%H:%M:%S)"
  _status "error" "Dump SQL corrompido"
  rm -rf "$TMP_DIR"; exit 1
fi

SQL_SIZE=$(du -sh "${TMP_DIR}/alfahome_${DATE}.sql.gz" | cut -f1)
_log "  ✓ SQL: ${SQL_SIZE}"

# ─── 2. BACKUP STORAGE/ LARAVEL ──────────────────────────────────────────────
_log "[2/4] Backup do storage/ Laravel..."
STORAGE_TAR="${TMP_DIR}/alfahome_storage_${DATE}.tar.gz"

# Tenta localizar o storage: volume Docker ou diretório do projeto
if docker volume ls --format '{{.Name}}' | grep -q "alfahome_app-storage\|alfahome_app_storage\|app-storage\|app_storage"; then
  VOLUME_NAME=$(docker volume ls --format '{{.Name}}' | grep -E "alfahome.*(app.storage|storage)" | head -1)
  STORAGE_PATH="/var/lib/docker/volumes/${VOLUME_NAME}/_data"
  if [ -d "$STORAGE_PATH" ]; then
    tar -czf "${STORAGE_TAR}" -C "${STORAGE_PATH}" . 2>/dev/null
    _log "  ✓ Storage (volume Docker): $(du -sh "${STORAGE_TAR}" | cut -f1)"
  fi
elif [ -d "${PROJECT_DIR}/storage" ]; then
  tar -czf "${STORAGE_TAR}" -C "${PROJECT_DIR}" storage/ 2>/dev/null
  _log "  ✓ Storage (diretório): $(du -sh "${STORAGE_TAR}" | cut -f1)"
else
  _log "  ⚠ Storage não localizado — pulando"
fi

# ─── 3. BACKUP CONFIGS DO SERVIDOR ───────────────────────────────────────────
_log "[3/4] Backup de configurações do servidor..."
CONFIG_DIR="${TMP_DIR}/configs"
mkdir -p "$CONFIG_DIR"

# Arquivo .env (contém segredos — mantido apenas no GDrive)
for ENV_FILE in "${PROJECT_DIR}/.env.production" "${PROJECT_DIR}/.env"; do
  if [ -f "$ENV_FILE" ]; then
    cp "$ENV_FILE" "${CONFIG_DIR}/$(basename "$ENV_FILE")"
    _log "  ✓ $(basename "$ENV_FILE")"
  fi
done

# Certificados Let's Encrypt
if [ -d /etc/letsencrypt ]; then
  tar -czf "${CONFIG_DIR}/letsencrypt_${DATE}.tar.gz" \
    -C /etc letsencrypt/ 2>/dev/null
  _log "  ✓ Let's Encrypt: $(du -sh "${CONFIG_DIR}/letsencrypt_${DATE}.tar.gz" | cut -f1)"
fi

# Crontab
crontab -l > "${CONFIG_DIR}/crontab_root.txt" 2>/dev/null && _log "  ✓ crontab" || true

# ─── 4. UPLOAD GOOGLE DRIVE ──────────────────────────────────────────────────
_log "[4/4] Enviando ao Google Drive..."
rclone copy "${TMP_DIR}" "${GDRIVE_REMOTE}/${DATE}/" 2>&1 | tail -3
_log "  ✓ Upload concluído"

# Atualiza cache de backups disponíveis
/opt/alfahome/backup/list_backups.sh 2>/dev/null || true

# Cleanup local
rm -rf "$TMP_DIR"

# Retenção GFS
_log "Aplicando política de retenção GFS..."
_gfs_cleanup

DURACAO=$(( $(date +%s) - INICIO ))
[ "$DURACAO" -ge 60 ] && DUR_FMT="$((DURACAO/60))m $((DURACAO%60))s" || DUR_FMT="${DURACAO}s"

_notify "✅ Backup AlfaHome concluído
📦 SQL: ${SQL_SIZE}
📅 ${DATE} | ⏱ ${DUR_FMT}"

_log "=== Backup concluído com sucesso ==="
_status "success" "Backup concluído com sucesso."
