#!/usr/bin/env bash
# SAF — Bootstrap BD local MySQL 8 (schema CakePHP 5.3 + tipos_documento + mejoras v2)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SCHEMA_DIR="${ROOT}/config/schema"

# Credenciales por defecto (instancia local / Docker)
export SAF_DB_HOST="${SAF_DB_HOST:-127.0.0.1}"
export SAF_DB_PORT="${SAF_DB_PORT:-3307}"
export SAF_DB_USER="${SAF_DB_USER:-admin}"
export SAF_DB_PASS="${SAF_DB_PASS:-Zuft_2026.}"
export SAF_DB_NAME="${SAF_DB_NAME:-saf}"

SAF_MYSQL_SOCKET="${SAF_MYSQL_SOCKET:-/tmp/saf-mysqld.sock}"
WAIT_SECONDS="${SAF_MYSQL_WAIT_SECONDS:-90}"

MYSQL_BIN="$(command -v mysql || true)"
if [[ -z "${MYSQL_BIN}" ]]; then
  echo "FAIL: no se encontró el cliente mysql en PATH" >&2
  exit 1
fi

mysql_args=()
mysql_admin_args=()

if [[ -S "${SAF_MYSQL_SOCKET}" ]]; then
  echo "==> MySQL: socket ${SAF_MYSQL_SOCKET}"
  mysql_args=(--protocol=SOCKET -S "${SAF_MYSQL_SOCKET}")
  mysql_admin_args=(--protocol=SOCKET -S "${SAF_MYSQL_SOCKET}")
  export SAF_DB_SOCKET="${SAF_MYSQL_SOCKET}"
  export SAF_DB_HOST="${SAF_DB_HOST}"
else
  echo "==> MySQL: esperando ${SAF_DB_HOST}:${SAF_DB_PORT} (máx ${WAIT_SECONDS}s)…"
  elapsed=0
  until "${MYSQL_BIN}" -h "${SAF_DB_HOST}" -P "${SAF_DB_PORT}" -u "${SAF_DB_USER}" -p"${SAF_DB_PASS}" -e "SELECT 1" >/dev/null 2>&1; do
    if (( elapsed >= WAIT_SECONDS )); then
      echo "FAIL: MySQL no respondió en ${SAF_DB_HOST}:${SAF_DB_PORT} ni existe socket ${SAF_MYSQL_SOCKET}" >&2
      exit 1
    fi
    sleep 2
    elapsed=$((elapsed + 2))
  done
  echo "==> MySQL disponible en puerto ${SAF_DB_PORT}"
  mysql_args=(-h "${SAF_DB_HOST}" -P "${SAF_DB_PORT}")
  mysql_admin_args=(-h "${SAF_DB_HOST}" -P "${SAF_DB_PORT}")
  unset SAF_DB_SOCKET
  export SAF_DB_HOST SAF_DB_PORT
fi

mysql_cmd() {
  "${MYSQL_BIN}" "${mysql_args[@]}" -u "${SAF_DB_USER}" -p"${SAF_DB_PASS}" "$@"
}

mysql_admin_cmd() {
  "${MYSQL_BIN}" "${mysql_admin_args[@]}" -u "${SAF_DB_USER}" -p"${SAF_DB_PASS}" "$@"
}

# Config Cake opcional para apuntar a la instancia local (puerto 3307)
LOCAL_CFG="${ROOT}/config/app_local_mysql_local.php"
cat > "${LOCAL_CFG}" <<'PHP'
<?php
/**
 * Generado por bin/bootstrap_local_db.sh — conexión MySQL local (puerto 3307).
 * Para usarlo: copia o fusiona en config/app_local.php, o exporta SAF_DB_* al CLI.
 */
return [
    'Datasources' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => '3307',
            'username' => 'admin',
            'password' => 'Zuft_2026.',
            'database' => 'saf',
        ],
    ],
];
PHP
echo "==> Escrito ${LOCAL_CFG} (fusionar en app_local.php si la app web debe usar 3307)"

echo "==> DROP + CREATE DATABASE ${SAF_DB_NAME}"
mysql_admin_cmd -e "DROP DATABASE IF EXISTS \`${SAF_DB_NAME}\`; CREATE DATABASE \`${SAF_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Importando bootstrap_saf_completo.sql"
mysql_cmd "${SAF_DB_NAME}" < "${SCHEMA_DIR}/bootstrap_saf_completo.sql"

echo "==> Importando tipos_documento.sql"
mysql_cmd "${SAF_DB_NAME}" < "${SCHEMA_DIR}/tipos_documento.sql"

echo "==> Aplicando patch_mejoras_v2.php (SAF_DB_*=${SAF_DB_HOST}:${SAF_DB_PORT})"
cd "${ROOT}"
php bin/aplicar_mejoras_v2.php

echo ""
echo "OK — Base ${SAF_DB_NAME} lista."
echo "  Superadmin: superadmin@saf.local / Admin123!"
echo "  Consultor:  consultor@saf.local / Admin123!"
echo ""
echo "Para Cake en web con puerto 3307, ajusta config/app_local.php según ${LOCAL_CFG}"
