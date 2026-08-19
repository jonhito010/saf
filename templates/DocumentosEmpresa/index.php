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

use App\Model\Table\DocumentosEmpresaTable;

echo $this->element('documentos_index', [
    'titulo' => 'Documentos de empresa',
    'subtitulo' => 'Actas, permisos, constancias fiscales y demás documentación corporativa',
    'documentos' => $documentos,
    'docStats' => $docStats,
    'tiposDocumento' => $tiposDocumento,
    'filtroQ' => $filtroQ,
    'filtroEstatus' => $filtroEstatus,
    'filtroTipo' => $filtroTipo,
    'urlIndex' => ['controller' => 'DocumentosEmpresa', 'action' => 'index'],
    'urlAdd' => ['controller' => 'DocumentosEmpresa', 'action' => 'add'],
    'modulo' => 'empresa',
    'tipoLabelFn' => [DocumentosEmpresaTable::class, 'tipoLabel'],
]);
