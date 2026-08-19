<?php
header('Content-Type: application/json; charset=utf-8');
$out = ['ok' => true, 'time' => date('c'), 'php' => PHP_VERSION];
try {
    $pdo = new PDO('mysql:unix_socket=/tmp/saf-mysqld.sock;dbname=saf', 'admin', 'Zuft_2026.');
    $out['db'] = 'ok';
    $out['usuarios'] = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $out['alertas'] = (int)$pdo->query('SELECT COUNT(*) FROM alertas_cumplimiento WHERE resuelto=0')->fetchColumn();
    $out['tablas_v2'] = [];
    foreach (['alertas_reglas','notificaciones','sistema_logs'] as $t) {
        $out['tablas_v2'][$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    }
} catch (Throwable $e) {
    $out['ok'] = false;
    $out['db'] = $e->getMessage();
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
