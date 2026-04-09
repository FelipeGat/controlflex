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
  # Lista arquivos da data
  FILES=$(rclone ls "${GDRIVE_REMOTE}/${D}/" 2>/dev/null)

  SQL_FILE=$(echo "$FILES" | grep "alfahome_${D}\.sql\.gz" | awk '{print $NF}' | head -1)
  SQL_SIZE=$(echo "$FILES" | grep "alfahome_${D}\.sql\.gz" | awk '{print $1}' | head -1)
  HAS_STORAGE=$(echo "$FILES" | grep -q "alfahome_storage_${D}\.tar\.gz" && echo "true" || echo "false")

  [ -z "$SQL_SIZE" ] && SQL_SIZE=0

  $FIRST || ENTRIES+=","
  FIRST=false
  ENTRIES+="{\"date\":\"${D}\",\"sqlSizeBytes\":${SQL_SIZE},\"hasStorage\":${HAS_STORAGE}}"
done

ENTRIES+="]"
echo "{\"entries\":${ENTRIES},\"updated_at\":\"$(date -Iseconds)\"}" > "${RUN_DIR}/backups.json"

COUNT=$(echo "$ENTRIES" | grep -o '"date"' | wc -l | tr -d ' ')
echo "backups.json atualizado: ${COUNT} entrada(s)"
