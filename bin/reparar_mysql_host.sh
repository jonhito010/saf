#!/usr/bin/env bash
# =============================================================================
# Reparar MySQL del HOST (puerto 3306) — ejecutar FUERA del sandbox Cursor
# como root real del servidor (SSH directo):
#   sudo bash /var/www/html/saf/bin/reparar_mysql_host.sh
# =============================================================================
set -euo pipefail

echo "==> 1. Quitar overlay RO de /run si existe"
if mount | grep -q 'none on /run type tmpfs (ro'; then
  umount -l /run 2>/dev/null || true
fi
mkdir -p /var/run/mysqld
# Dueño: mysql o nobody según el datadir
OWNER=$(stat -c '%U' /var/lib/mysql 2>/dev/null || echo mysql)
GROUP=$(stat -c '%G' /var/lib/mysql 2>/dev/null || echo mysql)
chown "$OWNER:$GROUP" /var/run/mysqld
chmod 755 /var/run/mysqld
echo "    socket dir listo (owner=$OWNER)"

echo "==> 2. Detener mysqld zombie"
pkill -TERM -x mysqld 2>/dev/null || true
sleep 3
pkill -9 -x mysqld 2>/dev/null || true
sleep 1

echo "==> 3. Arrancar MySQL del sistema"
if command -v systemctl >/dev/null && systemctl list-unit-files | grep -q mysql; then
  systemctl start mysql || service mysql start
else
  service mysql start
fi
sleep 4

echo "==> 4. Ping"
if mysqladmin --socket=/var/run/mysqld/mysqld.sock ping --silent \
  || mysqladmin -h127.0.0.1 -P3306 ping --silent; then
  echo "    MySQL 3306 OK"
else
  echo "    FAIL: MySQL no responde. Revisa /var/log/mysql/error.log"
  exit 1
fi

echo "==> 5. Aplicar patch SAF v2 sobre BD real"
cd /var/www/html/saf
# Restaurar app_local a 3306 si hace falta
php -r '
$file="config/app_local.php";
$c=file_get_contents($file);
$c=preg_replace("/'\''unix_socket'\''\s*=>\s*'\''[^'\'']*'\'',\s*/", "", $c);
$c=preg_replace("/'\''port'\''\s*=>\s*'\''3307'\''/", "'\''port'\'' => '\''3306'\''", $c);
$c=preg_replace("/'\''host'\''\s*=>\s*'\''localhost'\''/", "'\''host'\'' => '\''127.0.0.1'\''", $c);
file_put_contents($file,$c);
echo "app_local ajustado\n";
'

php bin/aplicar_mejoras_v2.php
SAF_DB_HOST=127.0.0.1 SAF_DB_PORT=3306 SAF_DB_USER=admin SAF_DB_PASS='Zuft_2026.' SAF_DB_NAME=saf \
  php bin/verificar_mejoras_v2.php || true

echo "==> 6. Apache Alias SAF"
cp -f config/apache-saf.conf /etc/apache2/conf-available/saf.conf
a2enconf saf
apache2ctl configtest && systemctl reload apache2 || service apache2 reload

echo "LISTO. Prueba: http://$(hostname -I | awk '{print $1}')/saf/login"
