<?php
declare(strict_types=1);

/**
 * Aplica patch_mejoras_v2.sql de forma idempotente.
 * Uso: php bin/aplicar_mejoras_v2.php
 *
 * Override por variables de entorno (bootstrap local / Docker):
 *   SAF_DB_HOST, SAF_DB_PORT, SAF_DB_USER, SAF_DB_PASS, SAF_DB_NAME
 */
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

/**
 * @return array{host: string, port: string, username: string, password: string, database: string, socket: string}|null
 */
function safDbConfigFromEnv(): ?array
{
    $host = getenv('SAF_DB_HOST');
    $socket = getenv('SAF_DB_SOCKET');
    $hasHost = $host !== false && trim((string)$host) !== '';
    $hasSocket = $socket !== false && trim((string)$socket) !== '';
    if (!$hasHost && !$hasSocket) {
        return null;
    }

    return [
        'host' => $hasHost ? (string)$host : '127.0.0.1',
        'port' => (string)(getenv('SAF_DB_PORT') ?: '3306'),
        'username' => (string)(getenv('SAF_DB_USER') ?: 'root'),
        'password' => (string)(getenv('SAF_DB_PASS') ?: ''),
        'database' => (string)(getenv('SAF_DB_NAME') ?: 'saf'),
        'socket' => $hasSocket ? (string)$socket : '',
    ];
}

/** Wrapper mínimo compatible con ConnectionManager::execute() vía PDO. */
final class SafPdoStatement
{
    public function __construct(private readonly \PDOStatement $stmt)
    {
    }

    /** @return array<string, mixed>|false */
    public function fetch(string $mode = 'assoc'): array|false
    {
        unset($mode);

        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
}

final class SafPdoConnection
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function execute(string $sql, array $params = []): SafPdoStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return new SafPdoStatement($stmt);
    }
}

/**
 * @param array{host: string, port: string, username: string, password: string, database: string, socket?: string} $config
 */
function safConnectPdo(array $config): SafPdoConnection
{
    $socket = trim((string)($config['socket'] ?? ''));
    if ($socket !== '') {
        $dsn = sprintf(
            'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
            $socket,
            $config['database'],
        );
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database'],
        );
    }
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return new SafPdoConnection($pdo);
}

$envConfig = safDbConfigFromEnv();
$usingEnv = $envConfig !== null;

if ($usingEnv) {
    $conn = safConnectPdo($envConfig);
    $config = $envConfig;
} else {
    require $root . '/config/bootstrap.php';

    $conn = ConnectionManager::get('default');
    $config = Configure::read('Datasources.default');
}

$sqlFile = $root . '/config/schema/patch_mejoras_v2.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "FAIL: no existe {$sqlFile}\n");
    exit(1);
}

$dbName = (string)($config['database'] ?? '');

echo "=== SAF · aplicar_mejoras_v2 ===\n";
echo 'Base de datos: ' . ($dbName !== '' ? $dbName : '(desde conexión)') . "\n";
if ($usingEnv) {
    $via = trim((string)($config['socket'] ?? '')) !== ''
        ? 'socket ' . $config['socket']
        : ($config['host'] ?? '') . ':' . ($config['port'] ?? '');
    echo 'Conexión: PDO vía SAF_DB_* (' . $via . ")\n";
}
echo "\n";

/** @var list<string> */
$okLog = [];
/** @var list<string> */
$failLog = [];

$registrarOk = static function (string $msg) use (&$okLog): void {
    $okLog[] = $msg;
    echo "[OK] {$msg}\n";
};

$registrarFail = static function (string $msg) use (&$failLog): void {
    $failLog[] = $msg;
    echo "[FAIL] {$msg}\n";
};

/**
 * @return list<string>
 */
function safParseSqlFile(string $path): array
{
    $raw = (string)file_get_contents($path);
    $statements = [];
    $lines = explode("\n", $raw);
    $buffer = '';
    $delimiter = ';';
    $inProcedure = false;

    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }

        if (preg_match('/^DELIMITER\s+(\S+)/i', $trim, $m)) {
            if ($buffer !== '') {
                $statements[] = trim($buffer);
                $buffer = '';
            }
            $delimiter = $m[1];
            $inProcedure = $delimiter !== ';';
            continue;
        }

        $buffer .= $line . "\n";

        if ($delimiter === ';') {
            if (str_ends_with(rtrim($line), ';')) {
                $stmt = trim($buffer);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $buffer = '';
            }
            continue;
        }

        if ($inProcedure && str_ends_with($trim, $delimiter)) {
            $stmt = trim(substr($buffer, 0, -strlen($delimiter)));
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
            $inProcedure = false;
            $delimiter = ';';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return array_values(array_filter($statements, static fn(string $s): bool => $s !== ''));
}

/**
 * @param list<string> $statements
 */
function safEjecutarStatements($conn, array $statements, callable $ok, callable $fail): void
{
    foreach ($statements as $stmt) {
        $preview = preg_replace('/\s+/', ' ', $stmt) ?? $stmt;
        $preview = mb_substr($preview, 0, 100);

        try {
            $conn->execute($stmt);
            $ok('SQL: ' . $preview . (mb_strlen($preview) >= 100 ? '…' : ''));
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'Duplicate column') !== false
                || stripos($msg, 'already exists') !== false
                || stripos($msg, 'Duplicate entry') !== false
            ) {
                $ok('SQL (ya aplicado): ' . $preview . '…');
                continue;
            }
            $fail('SQL: ' . $preview . '… → ' . $msg);
        }
    }
}

/**
 * Fallback cuando los procedimientos no pueden ejecutarse (permisos, hosting, etc.).
 */
function safAplicarFallback($conn, callable $ok, callable $fail): void
{
    echo "\n--- Fallback ALTER / CREATE ---\n";

    $columnas = [
        'usuarios' => [
            'suspendido_at' => '`suspendido_at` DATETIME NULL DEFAULT NULL',
            'motivo_suspension' => '`motivo_suspension` VARCHAR(255) NULL DEFAULT NULL',
            'es_cuenta' => '`es_cuenta` TINYINT(1) NOT NULL DEFAULT 0 COMMENT \'1 = cuenta titular\'',
        ],
        'empresas' => [
            'suspendida_at' => '`suspendida_at` DATETIME NULL DEFAULT NULL',
            'motivo_suspension' => '`motivo_suspension` VARCHAR(255) NULL DEFAULT NULL',
        ],
        'operadores' => [
            'baja_at' => '`baja_at` DATETIME NULL DEFAULT NULL',
            'baja_motivo' => '`baja_motivo` VARCHAR(255) NULL DEFAULT NULL',
            'baja_usuario_id' => '`baja_usuario_id` INT UNSIGNED NULL DEFAULT NULL',
        ],
        'alertas_cumplimiento' => [
            'leida' => '`leida` TINYINT(1) NOT NULL DEFAULT 0',
            'leida_at' => '`leida_at` DATETIME NULL DEFAULT NULL',
            'documento_id' => '`documento_id` INT UNSIGNED NULL DEFAULT NULL',
            'url_destino' => '`url_destino` VARCHAR(255) NULL DEFAULT NULL',
            'dias_restantes' => '`dias_restantes` INT NULL DEFAULT NULL',
            'entidad_nombre' => '`entidad_nombre` VARCHAR(180) NULL DEFAULT NULL',
            'empresa_nombre' => '`empresa_nombre` VARCHAR(180) NULL DEFAULT NULL',
            'tipo_documento_label' => '`tipo_documento_label` VARCHAR(150) NULL DEFAULT NULL',
        ],
        'tipos_documento' => [
            'editable' => '`editable` TINYINT(1) NOT NULL DEFAULT 0',
        ],
    ];

    foreach ($columnas as $tabla => $cols) {
        foreach ($cols as $col => $def) {
            try {
                $exists = (int)$conn->execute(
                    'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
                    ['t' => $tabla, 'c' => $col],
                )->fetch('assoc')['c'];
                if ($exists > 0) {
                    $ok("Columna {$tabla}.{$col} (ya existe)");
                    continue;
                }
                $conn->execute("ALTER TABLE `{$tabla}` ADD COLUMN {$def}");
                $ok("Columna {$tabla}.{$col} creada");
            } catch (Throwable $e) {
                $fail("Columna {$tabla}.{$col}: " . $e->getMessage());
            }
        }
    }

    $creates = [
        'alertas_reglas' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `alertas_reglas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dias_antes` INT NOT NULL,
  `nombre` VARCHAR(80) NOT NULL,
  `enviar_alerta` TINYINT(1) NOT NULL DEFAULT 1,
  `ocultar_despues` TINYINT(1) NOT NULL DEFAULT 0,
  `mantener_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `nivel_urgencia` ENUM('info','advertencia','critico') NOT NULL DEFAULT 'info',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regla_dias_empresa` (`dias_antes`, `empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'notificaciones_flujo' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `notificaciones_flujo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `orden` INT NOT NULL DEFAULT 1,
  `rol_id` INT NOT NULL,
  `rol_nombre` VARCHAR(60) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'notificaciones' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NULL,
  `alerta_id` INT UNSIGNED NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `mensaje` VARCHAR(500) NOT NULL,
  `url_destino` VARCHAR(255) NULL,
  `tipo` VARCHAR(40) NOT NULL DEFAULT 'alerta',
  `leida` TINYINT(1) NOT NULL DEFAULT 0,
  `leida_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_usuario_leida` (`usuario_id`, `leida`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'verificaciones_historial' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `verificaciones_historial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `operador_id` INT UNSIGNED NULL,
  `vehiculo_id` INT UNSIGNED NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(60) NOT NULL DEFAULT 'verificacion',
  `resultado` VARCHAR(40) NOT NULL,
  `detalle` TEXT NULL,
  `fecha` DATETIME NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'auditoria_cambios' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `auditoria_cambios` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL,
  `usuario_id` INT UNSIGNED NULL,
  `entidad_tipo` VARCHAR(60) NOT NULL,
  `entidad_id` INT UNSIGNED NOT NULL,
  `campo` VARCHAR(80) NOT NULL,
  `valor_anterior` TEXT NULL,
  `valor_nuevo` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'sistema_logs' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `sistema_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `empresa_id` INT UNSIGNED NULL,
  `accion` VARCHAR(60) NOT NULL,
  `modulo` VARCHAR(60) NULL,
  `descripcion` VARCHAR(500) NOT NULL,
  `detalle_json` JSON NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `nivel` ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    ];

    foreach ($creates as $tabla => $ddl) {
        try {
            $conn->execute($ddl);
            $ok("Tabla {$tabla} verificada/creada");
        } catch (Throwable $e) {
            $fail("Tabla {$tabla}: " . $e->getMessage());
        }
    }

    $seeds = [
        "INSERT INTO alertas_reglas (dias_antes, nombre, enviar_alerta, ocultar_despues, mantener_visible, nivel_urgencia, activo, orden, empresa_id, created_at)
         SELECT 45, 'Aviso 45 días', 1, 0, 1, 'info', 1, 1, NULL, NOW()
         WHERE NOT EXISTS (SELECT 1 FROM alertas_reglas LIMIT 1)",
        "INSERT INTO alertas_reglas (dias_antes, nombre, enviar_alerta, ocultar_despues, mantener_visible, nivel_urgencia, activo, orden, empresa_id, created_at)
         SELECT 30, 'Aviso 30 días', 1, 0, 1, 'advertencia', 1, 2, NULL, NOW()
         WHERE (SELECT COUNT(*) FROM alertas_reglas) < 2",
        "INSERT INTO notificaciones_flujo (orden, rol_id, rol_nombre, activo, empresa_id, created_at)
         SELECT 1, 2, 'Consultor', 1, NULL, NOW()
         WHERE NOT EXISTS (SELECT 1 FROM notificaciones_flujo LIMIT 1)",
    ];

    foreach ($seeds as $seed) {
        try {
            $conn->execute($seed);
            $ok('Seed: ' . mb_substr(preg_replace('/\s+/', ' ', $seed) ?? $seed, 0, 60) . '…');
        } catch (Throwable $e) {
            $fail('Seed: ' . $e->getMessage());
        }
    }
}

// Intentar mysql client si está disponible (mejor soporte DELIMITER)
$host = (string)($config['host'] ?? 'localhost');
$port = (string)($config['port'] ?? '3306');
$user = (string)($config['username'] ?? 'root');
$pass = (string)($config['password'] ?? '');
$database = (string)($config['database'] ?? '');
$socket = trim((string)($config['socket'] ?? ''));

$mysqlBin = trim((string)shell_exec('command -v mysql 2>/dev/null'));
$usarMysqlCli = $mysqlBin !== '' && $database !== '';

if ($usarMysqlCli) {
    echo "--- Intento con mysql client ---\n";
    $cnf = tempnam(sys_get_temp_dir(), 'saf_mysql_');
    if ($cnf !== false) {
        if ($socket !== '') {
            $clientCnf = "[client]\nuser={$user}\npassword={$pass}\nsocket={$socket}\n";
        } else {
            $clientCnf = "[client]\nuser={$user}\npassword={$pass}\nhost={$host}\nport={$port}\n";
        }
        file_put_contents($cnf, $clientCnf);
        chmod($cnf, 0600);
        $cmd = sprintf(
            '%s --defaults-extra-file=%s %s < %s 2>&1',
            escapeshellarg($mysqlBin),
            escapeshellarg($cnf),
            escapeshellarg($database),
            escapeshellarg($sqlFile),
        );
        exec($cmd, $out, $code);
        @unlink($cnf);
        if ($code === 0) {
            $registrarOk('Patch aplicado vía mysql client');
        } else {
            $registrarFail('mysql client exit ' . $code . ': ' . implode(' ', array_slice($out, 0, 3)));
            echo "--- Reintento vía PDO con parser ---\n";
            safEjecutarStatements($conn, safParseSqlFile($sqlFile), $registrarOk, $registrarFail);
        }
    }
} else {
    echo "--- Aplicando vía PDO (parser SQL) ---\n";
    safEjecutarStatements($conn, safParseSqlFile($sqlFile), $registrarOk, $registrarFail);
}

// Si hubo fallos con procedimientos, aplicar fallback
$procedureFails = array_filter($failLog, static fn(string $m): bool => str_contains($m, 'saf_add_column') || str_contains($m, 'PROCEDURE'));
if ($procedureFails !== [] || $failLog !== []) {
    safAplicarFallback($conn, $registrarOk, $registrarFail);
}

echo "\n--- Post-proceso: marcar cuentas administradoras ---\n";
try {
    $conn->execute(
        'UPDATE usuarios SET es_cuenta = 1 WHERE rol_id IN (1, 2) AND (es_cuenta IS NULL OR es_cuenta = 0)',
    );
    $registrarOk('Administradores marcados es_cuenta=1');
} catch (Throwable $e) {
    $registrarFail('Marcar es_cuenta: ' . $e->getMessage());
}

echo "\n--- Verificación tablas críticas ---\n";
$tablasCriticas = [
    'alertas_reglas',
    'notificaciones',
    'notificaciones_flujo',
    'verificaciones_historial',
    'auditoria_cambios',
    'sistema_logs',
];
$tablasOk = 0;
foreach ($tablasCriticas as $tabla) {
    try {
        $exists = (int)$conn->execute(
            'SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t',
            ['t' => $tabla],
        )->fetch('assoc')['c'];
        if ($exists > 0) {
            $registrarOk("Tabla crítica presente: {$tabla}");
            $tablasOk++;
        } else {
            $registrarFail("Tabla crítica ausente: {$tabla}");
        }
    } catch (Throwable $e) {
        $registrarFail("Tabla {$tabla}: " . $e->getMessage());
    }
}

echo "\n=== RESUMEN ===\n";
echo 'OK:   ' . count($okLog) . "\n";
echo 'FAIL: ' . count($failLog) . "\n";
echo "Tablas críticas: {$tablasOk}/" . count($tablasCriticas) . "\n";

if ($tablasOk === count($tablasCriticas)) {
    echo "RESULTADO: OK (tablas críticas presentes)\n";
    exit(0);
}

echo "RESULTADO: FAIL (faltan tablas críticas)\n";
exit(1);
