<?php
/**
 * Pruebas funcionales ligeras (sin BD) para validar firmas y reglas de negocio.
 * Ejecutar: php tests/functional_smoke_mejoras_v2.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

use App\Service\AlertasReglasService;
use App\Service\ExportacionService;

$failed = 0;
$ok = static function (string $label) {
    echo "[OK] {$label}\n";
};
$fail = static function (string $label, string $detail = '') use (&$failed) {
    $failed++;
    echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
};

// 1) Texto de alerta nunca es solo "vence"
$t1 = AlertasReglasService::textoRecordatorio('Licencia Federal', 15, false, 'Juan Pérez');
if (stripos($t1, 'Licencia Federal') !== false && stripos($t1, 'Faltan') !== false) {
    $ok('textoRecordatorio incluye tipo y días');
} else {
    $fail('textoRecordatorio', $t1);
}

$t2 = AlertasReglasService::textoRecordatorio('Póliza', 0, true);
if (stripos($t2, 'Póliza') !== false && stripos($t2, 'Vencido') !== false) {
    $ok('textoRecordatorio vencido con tipo');
} else {
    $fail('textoRecordatorio vencido', $t2);
}

// 2) Umbrales fallback incluyen 7 y 1
$umbrales = AlertasReglasService::UMBRALES_DIAS;
if ($umbrales === [45, 30, 15, 7, 1]) {
    $ok('UMBRALES_DIAS = [45,30,15,7,1]');
} else {
    $fail('UMBRALES_DIAS', json_encode($umbrales) ?: '');
}

// 3) Exportación CSV / XLSX
$export = new ExportacionService();
$csv = $export->exportarCsv(
    ['Empresa', 'Documento', 'Días'],
    [['Acme', 'Licencia Federal', 12]],
    'prueba.csv',
);
if (($csv['contentType'] ?? '') !== '' && str_contains((string)$csv['content'], 'Licencia Federal')) {
    $ok('ExportacionService CSV');
} else {
    $fail('ExportacionService CSV');
}

$xlsx = $export->exportarXlsx(
    ['Empresa', 'Documento'],
    [['Acme', 'Apto Médico']],
    'prueba.xlsx',
);
if (str_starts_with((string)($xlsx['content'] ?? ''), 'PK') && str_contains((string)$xlsx['contentType'], 'sheet')) {
    $ok('ExportacionService XLSX (ZIP OOXML)');
} else {
    $fail('ExportacionService XLSX');
}

// 4) Controllers existen
foreach ([
    'App\\Controller\\ReportesController',
    'App\\Controller\\AuditoriaController',
    'App\\Controller\\CuentasController',
    'App\\Controller\\NotificacionesController',
    'App\\Controller\\ReglasAlertasController',
    'App\\Controller\\TiposDocumentoController',
    'App\\Controller\\VerificacionesController',
] as $class) {
    if (class_exists($class)) {
        $ok("Clase {$class}");
    } else {
        $fail("Clase {$class}");
    }
}

// 5) Métodos de operadores
$ref = new ReflectionClass('App\\Controller\\OperadoresController');
foreach (['cambiarEstatus', 'delete', 'add'] as $m) {
    if ($ref->hasMethod($m)) {
        $ok("OperadoresController::{$m}");
    } else {
        $fail("OperadoresController::{$m}");
    }
}

echo $failed === 0 ? "\nTodas las pruebas pasaron.\n" : "\nFallaron {$failed} pruebas.\n";
exit($failed === 0 ? 0 : 1);
