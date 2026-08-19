<?php
/**
 * Validación del patch SAF v2 contra MySQL de producción (vía Apache).
 * Abrir: /saf/webroot/validar-patch-v2.php?key=SAF2026
 */
declare(strict_types=1);

if (($_GET['key'] ?? '') !== 'SAF2026') {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Validar patch SAF</title>';
echo '<style>body{font-family:system-ui;max-width:800px;margin:30px auto;padding:0 16px}';
echo 'table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px;text-align:left}';
echo '.ok{color:#0a7;font-weight:700}.fail{color:#c00;font-weight:700}</style></head><body>';
echo '<h1>Validación patch SAF v2</h1>';

$creds = [
    ['127.0.0.1', 3306, 'admin', 'Zuft_2026.'],
    ['localhost', 3306, 'admin', 'Zuft_2026.'],
];

$pdo = null;
$used = '';
foreach ($creds as [$host, $port, $user, $pass]) {
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname=saf;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
        );
        $used = "{$host}:{$port}";
        break;
    } catch (Throwable $e) {
        $last = $e->getMessage();
    }
}

if ($pdo === null) {
    echo '<p class="fail">No se pudo conectar a MySQL producción (3306).</p>';
    echo '<pre>' . htmlspecialchars($last ?? '') . '</pre>';
    echo '<p>Ejecuta en phpMyAdmin el archivo <code>config/schema/validar_patch_v2.sql</code> y pégame el resultado.</p>';
    echo '</body></html>';
    exit;
}

echo '<p class="ok">Conectado a ' . htmlspecialchars($used) . '/saf</p>';

$tablas = [
    'alertas_reglas',
    'notificaciones_flujo',
    'notificaciones',
    'verificaciones_historial',
    'auditoria_cambios',
    'sistema_logs',
];
$columnas = [
    ['usuarios', 'suspendido_at'],
    ['usuarios', 'es_cuenta'],
    ['operadores', 'baja_at'],
    ['alertas_cumplimiento', 'leida'],
    ['alertas_cumplimiento', 'url_destino'],
    ['alertas_cumplimiento', 'dias_restantes'],
    ['tipos_documento', 'editable'],
];

echo '<h2>Tablas</h2><table><tr><th>Tabla</th><th>Estado</th><th>Filas</th></tr>';
$okT = 0;
foreach ($tablas as $t) {
    try {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo '<tr><td>' . htmlspecialchars($t) . '</td><td class="ok">OK</td><td>' . $n . '</td></tr>';
        $okT++;
    } catch (Throwable $e) {
        echo '<tr><td>' . htmlspecialchars($t) . '</td><td class="fail">FALTA</td><td>—</td></tr>';
    }
}
echo '</table>';

echo '<h2>Columnas</h2><table><tr><th>Columna</th><th>Estado</th></tr>';
$okC = 0;
foreach ($columnas as [$tabla, $col]) {
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    );
    $st->execute([$tabla, $col]);
    $exists = (int)$st->fetchColumn() === 1;
    if ($exists) {
        $okC++;
        echo '<tr><td>' . htmlspecialchars("{$tabla}.{$col}") . '</td><td class="ok">OK</td></tr>';
    } else {
        echo '<tr><td>' . htmlspecialchars("{$tabla}.{$col}") . '</td><td class="fail">FALTA</td></tr>';
    }
}
echo '</table>';

$totalOk = ($okT === count($tablas)) && ($okC === count($columnas));
echo '<h2>Resultado</h2>';
if ($totalOk) {
    echo '<p class="ok">PATCH OK — Base lista para las mejoras v2</p>';
    // Restaurar app_local a 3306
    $appLocal = dirname(__DIR__, 2) . '/config/app_local.php';
    if (is_writable($appLocal)) {
        $c = file_get_contents($appLocal);
        $c = preg_replace("/'unix_socket'\\s*=>\\s*'[^']*',\\s*/", '', $c);
        $c = preg_replace("/'host'\\s*=>\\s*'localhost'/", "'host' => '127.0.0.1'", $c);
        $c = preg_replace("/'port'\\s*=>\\s*'3307'/", "'port' => '3306'", $c);
        // Si quedó comentario de socket, limpiar bloque viejo
        if (str_contains($c, 'unix_socket') === false && str_contains($c, "'port' => '3306'")) {
            file_put_contents($appLocal, $c);
            echo '<p class="ok">app_local.php apuntando a 127.0.0.1:3306</p>';
        } else {
            // Forzar escritura limpia del datasource default port/host
            file_put_contents($appLocal, $c);
            echo '<p class="ok">app_local.php actualizado</p>';
        }
    }
    echo '<p><a href="/saf/login">Ir al login</a></p>';
} else {
    echo '<p class="fail">PATCH INCOMPLETO — Tablas OK: ' . $okT . '/' . count($tablas) . ' · Columnas OK: ' . $okC . '/' . count($columnas) . '</p>';
}
echo '</body></html>';
