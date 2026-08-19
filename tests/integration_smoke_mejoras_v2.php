<?php
/**
 * Smoke de integración vía Cake (sin servidor HTTP).
 * Uso: php tests/integration_smoke_mejoras_v2.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use App\Service\AlertasReglasService;
use App\Service\NotificacionesService;
use App\Service\ExportacionService;
use App\Service\AuditoriaService;

$failed = 0;
$ok = static function (string $m): void { echo "[OK] {$m}\n"; };
$fail = static function (string $m) use (&$failed): void { $failed++; echo "[FAIL] {$m}\n"; };

try {
    $c = ConnectionManager::get('default');
    $db = (string)$c->execute('SELECT DATABASE()')->fetchColumn(0);
    $ok("Conexión BD ({$db})");
} catch (Throwable $e) {
    $fail('Conexión BD: ' . $e->getMessage());
    exit(1);
}

// Login credentials
$row = $c->execute(
    'SELECT password_hash, rol_id, activo FROM usuarios WHERE email = :e',
    ['e' => 'superadmin@saf.local'],
)->fetch('assoc');
if ($row && password_verify('Admin123!', (string)$row['password_hash']) && (int)$row['rol_id'] === 1) {
    $ok('Credenciales superadmin@saf.local');
} else {
    $fail('Credenciales superadmin');
}

// Tablas críticas
foreach (['alertas_reglas', 'notificaciones', 'sistema_logs', 'auditoria_cambios', 'verificaciones_historial'] as $t) {
    try {
        $c->execute("SELECT 1 FROM {$t} LIMIT 1");
        $ok("Tabla {$t}");
    } catch (Throwable $e) {
        $fail("Tabla {$t}");
    }
}

// Sync alertas
$alertas = TableRegistry::getTableLocator()->get('Alertas');
$n = $alertas->sincronizarDesdeDocumentos(1);
$cnt = (int)$c->execute('SELECT COUNT(*) FROM alertas_cumplimiento WHERE resuelto = 0')->fetchColumn(0);
if ($cnt > 0) {
    $ok("Alertas activas: {$cnt} (sync={$n})");
} else {
    $fail('Sin alertas activas');
}

$sample = $c->execute(
    "SELECT descripcion FROM alertas_cumplimiento WHERE resuelto = 0 AND descripcion LIKE '%Faltan%' LIMIT 1",
)->fetch('assoc');
if ($sample) {
    $ok('Texto alerta con Faltan XX días: ' . $sample['descripcion']);
} else {
    $fail('Texto alerta sin “Faltan”');
}

$notif = (int)$c->execute('SELECT COUNT(*) FROM notificaciones')->fetchColumn(0);
$ok("Notificaciones: {$notif}");

// Reglas
$reglas = AlertasReglasService::reglasActivas();
$ok('Reglas activas: ' . count($reglas));

// Export
$exp = (new ExportacionService())->exportarCsv(['A'], [['x']], 't.csv');
$ok('Export CSV bytes=' . strlen($exp['content']));

// Auditoría
(new AuditoriaService())->registrarLog('test', 'smoke', 'Prueba integración', null, 1, 1);
$logs = (int)$c->execute("SELECT COUNT(*) FROM sistema_logs WHERE accion = 'test'")->fetchColumn(0);
$ok("sistema_logs test rows={$logs}");

// Notificaciones service
$svc = new NotificacionesService();
$total = $svc->contarNoLeidas(1);
$ok("No leídas usuario 1: {$total}");

echo $failed === 0 ? "\nIntegración OK\n" : "\nFallaron {$failed}\n";
exit($failed === 0 ? 0 : 1);
