<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $documentos
 * @var array<string, int> $docStats
 * @var array<string, string> $tiposDocumento
 * @var string $filtroQ
 * @var string $filtroEstatus
 * @var string $filtroTipo
 */

use App\Model\Table\DocumentosVehiculoTable;

echo $this->element('documentos_index', [
    'titulo' => 'Documentos de vehículo',
    'subtitulo' => 'Expediente documental de todos los vehículos — seguros, verificaciones e inspecciones',
    'documentos' => $documentos,
    'docStats' => $docStats,
    'tiposDocumento' => $tiposDocumento,
    'filtroQ' => $filtroQ,
    'filtroEstatus' => $filtroEstatus,
    'filtroTipo' => $filtroTipo,
    'urlIndex' => ['controller' => 'DocumentosVehiculo', 'action' => 'index'],
    'urlAdd' => ['controller' => 'DocumentosVehiculo', 'action' => 'add'],
    'modulo' => 'vehiculo',
    'tipoLabelFn' => [DocumentosVehiculoTable::class, 'tipoLabel'],
]);
