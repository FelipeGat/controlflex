#!/bin/bash
# =============================================================================
# AlfaHome — Lista backups disponíveis no Google Drive
# Grava resultado em /opt/alfahome/backup/run/backups.json
# =============================================================================
RUN_DIR="/opt/alfahome/backup/run"
GDRIVE_REMOTE="gdrive:AlfaHome Backups"

mkdir -p "$RUN_DIR"

DATES=$(rclone lsd "${GDRIVE_REMOTE}/" 2>/dev/null \
  | awk '{print $NF}' \
  | grep -E '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' \
  | sort -r)

if [ -z "$DATES" ]; then
  echo '{"entries":[],"updated_at":"'"$(date -Iseconds)"'"}' > "${RUN_DIR}/backups.json"
  echo "Nenhum backup encontrado."
  exit 0
fi

ENTRIES="["
FIRST=true

for D in $DATES; do
  # Backup global
  GLOBAL_FILES=$(rclone ls "${GDRIVE_REMOTE}/${D}/global/" 2>/dev/null)
  SQL_SIZE=$(echo "$GLOBAL_FILES" | grep "alfahome_full_${D}\.sql\.gz" | awk '{print $1}' | head -1)
  HAS_STORAGE=$(echo "$GLOBAL_FILES" | grep -q "alfahome_storage_${D}\.tar\.gz" && echo "true" || echo "false")
  [ -z "$SQL_SIZE" ] && SQL_SIZE=0

  $FIRST || ENTRIES+=","
  FIRST=false
  ENTRIES+="{\"date\":\"${D}\",\"type\":\"global\",\"sqlSizeBytes\":${SQL_SIZE},\"hasStorage\":${HAS_STORAGE}}"

  # Backups por tenant
  TENANT_FOLDERS=$(rclone lsd "${GDRIVE_REMOTE}/${D}/clientes/" 2>/dev/null | awk '{print $NF}')
  while IFS= read -r FOLDER; do
    [ -z "$FOLDER" ] && continue
    TENANT_FILES=$(rclone ls "${GDRIVE_REMOTE}/${D}/clientes/${FOLDER}/" 2>/dev/null)
    T_SQL_FILE=$(echo "$TENANT_FILES" | awk '{print $NF}' | grep "\.sql\.gz$" | head -1)
    T_SQL_SIZE=$(echo "$TENANT_FILES" | grep "\.sql\.gz$" | awk '{print $1}' | head -1)
    [ -z "$T_SQL_SIZE" ] && T_SQL_SIZE=0
    # Extrai tenant_id do nome do arquivo: tenant_N_...
    TID=$(echo "$T_SQL_FILE" | sed 's/^tenant_\([0-9]*\)_.*/\1/')
    [[ "$TID" =~ ^[0-9]+$ ]] || TID="null"
    ENTRIES+=",{\"date\":\"${D}\",\"type\":\"tenant\",\"tenantId\":${TID},\"nome\":\"${FOLDER}\",\"sqlSizeBytes\":${T_SQL_SIZE}}"
  done <<< "$TENANT_FOLDERS"
done

ENTRIES+="]"
echo "{\"entries\":${ENTRIES},\"updated_at\":\"$(date -Iseconds)\"}" > "${RUN_DIR}/backups.json"

COUNT=$(echo "$ENTRIES" | grep -o '"date"' | wc -l | tr -d ' ')
echo "backups.json atualizado: ${COUNT} entrada(s)"
