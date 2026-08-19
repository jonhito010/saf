#!/usr/bin/env bash
# Arranca MySQL local SAF (demo / fallback mientras el host 3306 está caído)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATADIR="${SAF_MYSQL_DATADIR:-/tmp/saf-mysql-data}"
BACKUP="$ROOT/data/mysql-backup"
SOCK="${SAF_MYSQL_SOCKET:-/tmp/saf-mysqld.sock}"
PIDF="${SAF_MYSQL_PID:-/tmp/saf-mysqld.pid}"
LOG="${SAF_MYSQL_LOG:-/tmp/saf-mysqld-error.log}"
PORT="${SAF_MYSQL_PORT:-3307}"

if mysqladmin --socket="$SOCK" -uroot ping --silent 2>/dev/null; then
  echo "MySQL local ya activo ($SOCK)"
  exit 0
fi

if [[ ! -d "$DATADIR/mysql" ]]; then
  if [[ -d "$BACKUP/mysql" ]]; then
    echo "Restaurando datadir desde backup..."
    rm -rf "$DATADIR"
    cp -a "$BACKUP" "$DATADIR"
  else
    echo "ERROR: no hay datadir. Ejecuta: $ROOT/bin/bootstrap_local_db.sh"
    exit 1
  fi
fi

echo "Arrancando MySQL local puerto $PORT..."
/usr/sbin/mysqld --no-defaults \
  --user=root \
  --datadir="$DATADIR" \
  --socket="$SOCK" \
  --pid-file="$PIDF" \
  --port="$PORT" \
  --bind-address=127.0.0.1 \
  --mysqlx=0 \
  --log-error="$LOG" &
sleep 3
mysqladmin --socket="$SOCK" -uroot ping
echo "OK — app_local.php debe usar unix_socket=$SOCK"
