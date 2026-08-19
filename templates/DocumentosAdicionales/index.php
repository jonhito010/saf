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

$subtitulo = 'Reportes SICT, embarque, HIE, manifiestos, bitácoras y demás requisitos MP/residuos peligrosos';
if (!empty($alcanceLabel)) {
    $subtitulo .= ' · ' . $alcanceLabel;
}

echo $this->element('documentos_index', [
    'titulo' => 'Documentos adicionales',
    'subtitulo' => $subtitulo,
    'documentos' => $documentos,
    'docStats' => $docStats,
    'tiposDocumento' => $tiposDocumento,
    'filtroQ' => $filtroQ,
    'filtroEstatus' => $filtroEstatus,
    'filtroTipo' => $filtroTipo,
    'urlIndex' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
    'urlAdd' => ['controller' => 'DocumentosAdicionales', 'action' => 'add'],
    'modulo' => 'empresa',
    'editController' => 'DocumentosAdicionales',
    'tipoLabelFn' => [DocumentosEmpresaTable::class, 'tipoLabel'],
    'urlVolver' => ['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos'],
    'urlVolverLabel' => '← Materiales peligrosos',
    'origenQuery' => 'adicional',
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
]);
