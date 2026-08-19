<?php
/**
 * Aplicador web del patch SAF v2.
 * Se ejecuta en el contexto de Apache (red del host → MySQL 3306).
 *
 * Uso: abrir http://TU-SERVIDOR/saf/webroot/instalar-mejoras-v2.php?key=SAF2026
 * Borrar este archivo después de instalar.
 */
declare(strict_types=1);

$key = $_GET['key'] ?? '';
if ($key !== 'SAF2026') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Usa ?key=SAF2026\n";
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalar SAF v2</title>';
echo '<style>body{font-family:monospace;max-width:900px;margin:24px auto;padding:0 12px;white-space:pre-wrap}';
echo '.ok{color:#0a7}.fail{color:#c00}</style></head><body>';
echo "<h1>SAF · instalar mejoras v2</h1>\n";

$host = '127.0.0.1';
$port = 3306;
$user = 'admin';
$pass = 'Zuft_2026.';
$dbName = 'saf';

function line(string $cls, string $msg): void
{
    echo '<div class="' . htmlspecialchars($cls) . '">' . htmlspecialchars($msg) . "</div>\n";
    @ob_flush();
    @flush();
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    line('ok', "Conectado a {$host}:{$port}/{$dbName}");
} catch (Throwable $e) {
    line('fail', 'No se pudo conectar a MySQL host: ' . $e->getMessage());
    line('fail', 'Si falla, el MySQL 3306 sigue caído. Ejecuta por SSH: sudo bash /var/www/html/saf/bin/reparar_mysql_host.sh');
    echo '</body></html>';
    exit;
}

$sqlFile = dirname(__DIR__, 2) . '/config/schema/patch_mejoras_v2.sql';
if (!is_readable($sqlFile)) {
    line('fail', "No se lee {$sqlFile}");
    echo '</body></html>';
    exit;
}

$raw = file_get_contents($sqlFile);
// Quitar procedimientos (DELIMITER) — usar fallback ALTER/CREATE
$fallback = [
    "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS suspendido_at DATETIME NULL",
    "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS motivo_suspension VARCHAR(255) NULL",
    "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS es_cuenta TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS suspendida_at DATETIME NULL",
    "ALTER TABLE empresas ADD COLUMN IF NOT EXISTS motivo_suspension VARCHAR(255) NULL",
    "ALTER TABLE operadores ADD COLUMN IF NOT EXISTS baja_at DATETIME NULL",
    "ALTER TABLE operadores ADD COLUMN IF NOT EXISTS baja_motivo VARCHAR(255) NULL",
    "ALTER TABLE operadores ADD COLUMN IF NOT EXISTS baja_usuario_id INT UNSIGNED NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS leida TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS leida_at DATETIME NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS documento_id INT UNSIGNED NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS url_destino VARCHAR(255) NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS dias_restantes INT NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS entidad_nombre VARCHAR(180) NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS empresa_nombre VARCHAR(180) NULL",
    "ALTER TABLE alertas_cumplimiento ADD COLUMN IF NOT EXISTS tipo_documento_label VARCHAR(150) NULL",
    "ALTER TABLE tipos_documento ADD COLUMN IF NOT EXISTS editable TINYINT(1) NOT NULL DEFAULT 0",
];

$creates = <<<SQL
CREATE TABLE IF NOT EXISTS alertas_reglas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dias_antes INT NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  enviar_alerta TINYINT(1) NOT NULL DEFAULT 1,
  ocultar_despues TINYINT(1) NOT NULL DEFAULT 0,
  mantener_visible TINYINT(1) NOT NULL DEFAULT 1,
  nivel_urgencia ENUM('info','advertencia','critico') NOT NULL DEFAULT 'info',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  empresa_id INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_regla_dias_empresa (dias_antes, empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones_flujo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  orden INT NOT NULL DEFAULT 1,
  rol_id INT NOT NULL,
  rol_nombre VARCHAR(60) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  empresa_id INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  empresa_id INT UNSIGNED NULL,
  alerta_id INT UNSIGNED NULL,
  titulo VARCHAR(200) NOT NULL,
  mensaje VARCHAR(500) NOT NULL,
  url_destino VARCHAR(255) NULL,
  tipo VARCHAR(40) NOT NULL DEFAULT 'alerta',
  leida TINYINT(1) NOT NULL DEFAULT 0,
  leida_at DATETIME NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_notif_usuario_leida (usuario_id, leida, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS verificaciones_historial (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT UNSIGNED NOT NULL,
  operador_id INT UNSIGNED NULL,
  vehiculo_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(60) NOT NULL DEFAULT 'verificacion',
  resultado VARCHAR(40) NOT NULL,
  detalle TEXT NULL,
  fecha DATETIME NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_cambios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  entidad_tipo VARCHAR(60) NOT NULL,
  entidad_id INT UNSIGNED NOT NULL,
  campo VARCHAR(80) NOT NULL,
  valor_anterior TEXT NULL,
  valor_nuevo TEXT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sistema_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NULL,
  empresa_id INT UNSIGNED NULL,
  accion VARCHAR(60) NOT NULL,
  modulo VARCHAR(60) NULL,
  descripcion VARCHAR(500) NOT NULL,
  detalle_json JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  nivel ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

$ok = 0;
$fail = 0;

foreach ($fallback as $sql) {
    try {
        $pdo->exec($sql);
        line('ok', 'OK: ' . substr($sql, 0, 80));
        $ok++;
    } catch (Throwable $e) {
        // MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
            line('ok', 'SKIP (ya existe): ' . substr($sql, 0, 60));
            $ok++;
        } else {
            // Reintentar sin IF NOT EXISTS
            $alt = str_replace(' IF NOT EXISTS', '', $sql);
            try {
                $pdo->exec($alt);
                line('ok', 'OK(alt): ' . substr($alt, 0, 80));
                $ok++;
            } catch (Throwable $e2) {
                if (stripos($e2->getMessage(), 'Duplicate column') !== false) {
                    line('ok', 'SKIP: columna ya existe');
                    $ok++;
                } else {
                    line('fail', 'FAIL: ' . $e2->getMessage());
                    $fail++;
                }
            }
        }
    }
}

foreach (array_filter(array_map('trim', explode(';', $creates))) as $sql) {
    if ($sql === '') {
        continue;
    }
    try {
        $pdo->exec($sql);
        line('ok', 'OK CREATE: ' . substr($sql, 0, 60));
        $ok++;
    } catch (Throwable $e) {
        line('fail', 'FAIL CREATE: ' . $e->getMessage());
        $fail++;
    }
}

// Seeds reglas
try {
    $n = (int)$pdo->query('SELECT COUNT(*) FROM alertas_reglas')->fetchColumn();
    if ($n === 0) {
        $pdo->exec("INSERT INTO alertas_reglas (dias_antes,nombre,enviar_alerta,ocultar_despues,mantener_visible,nivel_urgencia,activo,orden,created_at) VALUES
          (45,'Aviso 45 días',1,0,1,'info',1,1,NOW()),
          (30,'Aviso 30 días',1,0,1,'advertencia',1,2,NOW()),
          (15,'Aviso 15 días',1,0,1,'advertencia',1,3,NOW()),
          (7,'Aviso 7 días',1,0,1,'critico',1,4,NOW()),
          (1,'Aviso 1 día',1,0,1,'critico',1,5,NOW())");
        line('ok', 'Seed alertas_reglas');
    } else {
        line('ok', "alertas_reglas ya tiene {$n} filas");
    }
    $ok++;
} catch (Throwable $e) {
    line('fail', $e->getMessage());
    $fail++;
}

try {
    $n = (int)$pdo->query('SELECT COUNT(*) FROM notificaciones_flujo')->fetchColumn();
    if ($n === 0) {
        $pdo->exec("INSERT INTO notificaciones_flujo (orden,rol_id,rol_nombre,activo,created_at) VALUES
          (1,2,'Consultor',1,NOW()),(2,3,'Usuario',1,NOW()),(3,1,'Super Administrador',0,NOW())");
        line('ok', 'Seed notificaciones_flujo');
    } else {
        line('ok', "notificaciones_flujo ya tiene {$n} filas");
    }
    $ok++;
} catch (Throwable $e) {
    line('fail', $e->getMessage());
    $fail++;
}

try {
    // es_cuenta si existe
    $pdo->exec('UPDATE usuarios SET es_cuenta = 1 WHERE rol_id IN (1,2)');
    line('ok', 'Administradores marcados es_cuenta=1');
    $ok++;
} catch (Throwable $e) {
    line('fail', 'es_cuenta: ' . $e->getMessage());
}

// Verificar críticas
$criticas = ['alertas_reglas','notificaciones','notificaciones_flujo','verificaciones_historial','auditoria_cambios','sistema_logs'];
$critOk = 0;
foreach ($criticas as $t) {
    try {
        $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1");
        line('ok', "Tabla crítica OK: {$t}");
        $critOk++;
    } catch (Throwable $e) {
        line('fail', "Falta tabla: {$t}");
    }
}

echo "\n<strong>Resumen: OK={$ok} FAIL={$fail} Críticas={$critOk}/6</strong>\n";

if ($critOk === 6) {
    // Actualizar app_local a 3306
    $appLocal = dirname(__DIR__, 2) . '/config/app_local.php';
    if (is_writable($appLocal)) {
        $c = file_get_contents($appLocal);
        $c = preg_replace("/'unix_socket'\\s*=>\\s*'[^']*',\\s*/", '', $c);
        $c = preg_replace("/'host'\\s*=>\\s*'localhost'/", "'host' => '127.0.0.1'", $c);
        $c = preg_replace("/'port'\\s*=>\\s*'3307'/", "'port' => '3306'", $c);
        // Asegurar comentarios limpios
        file_put_contents($appLocal, $c);
        line('ok', 'app_local.php restaurado a 127.0.0.1:3306');
    }
    echo "\n<p class=\"ok\"><strong>INSTALACIÓN COMPLETA.</strong> ";
    echo '<a href="/saf/login">Ir al login</a> · ';
    echo '<a href="/saf/webroot/estado-mejoras.php">Ver estado</a></p>';
    echo "<p class=\"fail\">IMPORTANTE: borra este archivo instalar-mejoras-v2.php por seguridad.</p>\n";
} else {
    echo "\n<p class=\"fail\">Instalación incompleta. Revisa errores arriba o ejecuta reparar_mysql_host.sh por SSH.</p>\n";
}

echo '</body></html>';
