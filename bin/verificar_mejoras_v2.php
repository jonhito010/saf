<?php
declare(strict_types=1);

/**
 * Verifica implementación de mejoras SAF v2 (BD, clases, rutas).
 * Uso: php bin/verificar_mejoras_v2.php
 */
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

use Cake\Datasource\ConnectionManager;
use App\Controller\UsuariosController;

echo "=== SAF · verificar_mejoras_v2 ===\n\n";

$failed = 0;
$passed = 0;

$check = static function (string $label, bool $ok, string $detail = '') use (&$failed, &$passed): void {
    if ($ok) {
        $passed++;
        echo "[✓] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    } else {
        $failed++;
        echo "[✗] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
};

$conn = ConnectionManager::get('default');

$tablas = [
    'alertas_reglas',
    'notificaciones_flujo',
    'notificaciones',
    'verificaciones_historial',
    'auditoria_cambios',
    'sistema_logs',
];

echo "--- Tablas ---\n";
foreach ($tablas as $tabla) {
    try {
        $exists = (int)$conn->execute(
            'SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t',
            ['t' => $tabla],
        )->fetch('assoc')['c'] > 0;
        $check("Tabla {$tabla}", $exists);
    } catch (Throwable $e) {
        $check("Tabla {$tabla}", false, $e->getMessage());
    }
}

$columnas = [
    ['usuarios', 'suspendido_at'],
    ['usuarios', 'motivo_suspension'],
    ['usuarios', 'es_cuenta'],
    ['operadores', 'baja_at'],
    ['operadores', 'baja_motivo'],
    ['alertas_cumplimiento', 'leida'],
    ['alertas_cumplimiento', 'url_destino'],
    ['tipos_documento', 'editable'],
];

echo "\n--- Columnas ---\n";
foreach ($columnas as [$tabla, $col]) {
    try {
        $exists = (int)$conn->execute(
            'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
            ['t' => $tabla, 'c' => $col],
        )->fetch('assoc')['c'] > 0;
        $check("Columna {$tabla}.{$col}", $exists);
    } catch (Throwable $e) {
        $check("Columna {$tabla}.{$col}", false, $e->getMessage());
    }
}

$clases = [
    'App\\Controller\\CuentasController',
    'App\\Controller\\UsuariosController',
    'App\\Controller\\AlertasController',
    'App\\Controller\\NotificacionesController',
    'App\\Controller\\DashboardController',
    'App\\Controller\\OperadoresController',
    'App\\Controller\\ReportesController',
    'App\\Controller\\ReglasAlertasController',
    'App\\Controller\\TiposDocumentoController',
    'App\\Controller\\VerificacionesController',
    'App\\Controller\\AuditoriaController',
    'App\\Service\\NotificacionesService',
    'App\\Service\\AlertasReglasService',
    'App\\Service\\AuditoriaService',
    'App\\Service\\ExportacionService',
];

echo "\n--- Clases PHP ---\n";
foreach ($clases as $class) {
    $check("Clase {$class}", class_exists($class));
}

$metodos = [
    ['App\\Controller\\CuentasController', 'suspender'],
    ['App\\Controller\\CuentasController', 'activar'],
    ['App\\Controller\\UsuariosController', 'suspender'],
    ['App\\Controller\\UsuariosController', 'activar'],
    ['App\\Controller\\AlertasController', 'ir'],
    ['App\\Controller\\OperadoresController', 'cambiarEstatus'],
    ['App\\Controller\\ReportesController', 'exportarCsv'],
    ['App\\Controller\\ReportesController', 'exportarXlsx'],
];

echo "\n--- Métodos controller ---\n";
foreach ($metodos as [$class, $method]) {
    $ok = class_exists($class) && method_exists($class, $method);
    $check("{$class}::{$method}()", $ok);
}

$roles = UsuariosController::ROLES;
echo "\n--- Roles ---\n";
$check('ROLES incluye Consultor (rol 2)', isset($roles[2]) && $roles[2] === 'Consultor', json_encode($roles, JSON_UNESCAPED_UNICODE) ?: '');

$routesFile = $root . '/config/routes.php';
$routesContent = is_file($routesFile) ? (string)file_get_contents($routesFile) : '';

$rutasEsperadas = [
    '/cuentas/suspender/{id}',
    '/cuentas/activar/{id}',
    '/accesos/suspender/{id}',
    '/accesos/activar/{id}',
    '/alertas/ir/{id}',
    '/notificaciones/panel',
    '/reportes',
    '/reportes/exportar-csv',
    '/reportes/exportar-xlsx',
    '/reglas-alertas',
    '/auditoria',
    '/tipos-documento',
    '/verificaciones',
    '/operadores/cambiar-estatus/{id}',
];

echo "\n--- Rutas (routes.php) ---\n";
foreach ($rutasEsperadas as $ruta) {
    $check("Ruta {$ruta}", str_contains($routesContent, $ruta));
}

$archivos = [
    'bin/aplicar_mejoras_v2.php',
    'bin/verificar_mejoras_v2.php',
    'templates/Alertas/index.php',
    'templates/Cuentas/index.php',
    'templates/Usuarios/index.php',
    'templates/layout/default.php',
    'webroot/js/saf.js',
    'config/schema/patch_mejoras_v2.sql',
];

echo "\n--- Archivos ---\n";
foreach ($archivos as $rel) {
    $check($rel, is_file($root . '/' . $rel));
}

echo "\n--- Checklist funcional (estático) ---\n";
$checklist = [
    'CuentasController suspender/activar' => method_exists('App\\Controller\\CuentasController', 'suspender'),
    'UsuariosController ROLES Consultor' => ($roles[2] ?? '') === 'Consultor',
    'AlertasController::ir' => method_exists('App\\Controller\\AlertasController', 'ir'),
    'NotificacionesService' => class_exists('App\\Service\\NotificacionesService'),
    'AlertasTable usa NotificacionesService' => str_contains(
        (string)@file_get_contents($root . '/src/Model/Table/AlertasTable.php'),
        'NotificacionesService',
    ),
    'Dashboard indicadoresGlobales' => str_contains(
        (string)@file_get_contents($root . '/templates/Dashboard/index.php'),
        'indicadoresGlobales',
    ),
    'Operadores 4 estatus' => str_contains(
        (string)@file_get_contents($root . '/src/Controller/OperadoresController.php'),
        "'suspendido'",
    ),
    'ReglasAlertas CRUD' => method_exists('App\\Controller\\ReglasAlertasController', 'add'),
    'TiposDocumento sin edit' => !method_exists('App\\Controller\\TiposDocumentoController', 'edit'),
    'Sesiones rechaza suspendidos' => str_contains(
        (string)@file_get_contents($root . '/src/Controller/SesionesController.php'),
        'Cuenta suspendida',
    ),
    'Layout menú Reportes' => str_contains($routesContent, "'controller' => 'Reportes'"),
    'saf.js CSRF token' => str_contains(
        (string)@file_get_contents($root . '/webroot/js/saf.js'),
        'X-CSRF-Token',
    ),
];

foreach ($checklist as $label => $ok) {
    $check($label, $ok);
}

echo "\n=== RESUMEN ===\n";
echo "Pasaron: {$passed}\n";
echo "Fallaron: {$failed}\n";

if ($failed === 0) {
    echo "RESULTADO: OK\n";
    exit(0);
}

echo "RESULTADO: FAIL\n";
exit(1);
