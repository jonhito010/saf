<?php
/**
 * Listado profesional de documentos (empresa o vehículo).
 *
 * @var \App\View\AppView $this
 * @var string $titulo
 * @var string $subtitulo
 * @var iterable $documentos
 * @var array<string, int> $docStats
 * @var array<string, string> $tiposDocumento
 * @var string $filtroQ
 * @var string $filtroEstatus
 * @var string $filtroTipo
 * @var array $urlIndex
 * @var array $urlAdd
 * @var string $modulo empresa|vehiculo
 * @var callable(string): string $tipoLabelFn
 * @var string|null $editController Controlador para editar (default según módulo)
 * @var array|null $urlVolver Enlace contextual secundario (ej. panel MP)
 * @var string|null $urlVolverLabel Etiqueta del enlace secundario
 * @var string|null $origenQuery Valor para ?origen= en editar
 */

$lista = is_array($documentos) ? $documentos : $documentos->toArray();
$editController = $editController ?? ($modulo === 'vehiculo' ? 'DocumentosVehiculo' : 'DocumentosEmpresa');
$origenQuery = $origenQuery ?? ($editController === 'DocumentosAdicionales' ? 'adicional' : null);
$badge = function (?string $estatus): string {
    return match ($estatus) {
        'vencido' => '<span class="badge badge-red">Vencido</span>',
        'por_vencer' => '<span class="badge badge-amber">Por vencer</span>',
        'vigente' => '<span class="badge badge-green">Vigente</span>',
        default => '<span class="badge badge-gray">—</span>',
    };
};
$rowClass = fn(?string $e): string => match ($e) {
    'vencido' => 'doc-index-row doc-index-row--crit',
    'por_vencer' => 'doc-index-row doc-index-row--warn',
    default => 'doc-index-row',
};
?>

<div class="veh-page doc-index-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1><?= h($titulo) ?></h1>
      <p><?= h($subtitulo) ?></p>
    </div>
    <div class="veh-header-actions">
      <?php if (!empty($urlVolver)): ?>
        <a class="btn btn-ghost" href="<?= $this->Url->build($urlVolver) ?>"><?= h($urlVolverLabel ?? '← Volver') ?></a>
      <?php elseif ($modulo === 'vehiculo'): ?>
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'index']) ?>">Vehículos</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>">Documentación empresa</a>
      <?php endif; ?>
      <a class="btn btn-primary" href="<?= $this->Url->build($urlAdd) ?>">+ Agregar documento</a>
    </div>
  </div>

  <?= $this->element('filtro_empresa', [
      'empresasFiltro' => $empresasFiltro ?? [],
      'filtroEmpresaId' => $filtroEmpresaId ?? null,
      'vistaConsolidada' => $vistaConsolidada ?? false,
      'alcanceLabel' => $alcanceLabel ?? '',
      'filtroPreserve' => [
          'q' => $filtroQ ?? '',
          'estatus' => $filtroEstatus ?? '',
          'tipo' => $filtroTipo ?? '',
      ],
  ]) ?>

  <div class="veh-kpi-grid doc-index-kpis">
    <div class="veh-kpi">
      <div class="veh-kpi-val"><?= (int)$docStats['total'] ?></div>
      <div class="veh-kpi-label">Total registros</div>
    </div>
    <div class="veh-kpi veh-kpi--ok">
      <div class="veh-kpi-val"><?= (int)$docStats['vigente'] ?></div>
      <div class="veh-kpi-label">Vigentes</div>
    </div>
    <div class="veh-kpi veh-kpi--warn">
      <div class="veh-kpi-val"><?= (int)$docStats['por_vencer'] ?></div>
      <div class="veh-kpi-label">Por vencer</div>
    </div>
    <div class="veh-kpi veh-kpi--crit">
      <div class="veh-kpi-val"><?= (int)$docStats['vencido'] ?></div>
      <div class="veh-kpi-label">Vencidos</div>
    </div>
  </div>

  <form class="veh-filters panel" method="get" action="<?= $this->Url->build($urlIndex) ?>">
    <div class="veh-filters-row">
      <div class="search-wrap veh-search">
        <svg class="search-icon" width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="search" name="q" value="<?= h($filtroQ) ?>" placeholder="Buscar por tipo, referencia o archivo…" autocomplete="off">
      </div>
      <select name="tipo" class="veh-select" aria-label="Tipo de documento">
        <option value="">Todos los tipos</option>
        <?php foreach ($tiposDocumento as $key => $label): ?>
          <option value="<?= h($key) ?>"<?= $filtroTipo === $key ? ' selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="estatus" class="veh-select" aria-label="Estatus">
        <option value="">Todo estatus</option>
        <option value="vigente"<?= $filtroEstatus === 'vigente' ? ' selected' : '' ?>>Vigente</option>
        <option value="por_vencer"<?= $filtroEstatus === 'por_vencer' ? ' selected' : '' ?>>Por vencer</option>
        <option value="vencido"<?= $filtroEstatus === 'vencido' ? ' selected' : '' ?>>Vencido</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
      <?php if ($filtroQ !== '' || $filtroEstatus !== '' || $filtroTipo !== ''): ?>
        <a href="<?= $this->Url->build($urlIndex) ?>" class="btn btn-ghost btn-sm">Limpiar</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($lista)): ?>
  <div class="panel veh-empty">
    <div class="veh-empty-icon" aria-hidden="true">📁</div>
    <h2>Sin documentos</h2>
    <p>No hay registros con los filtros aplicados o aún no se ha cargado ningún archivo.</p>
    <a class="btn btn-primary" href="<?= $this->Url->build($urlAdd) ?>">Agregar documento</a>
  </div>
  <?php else: ?>
  <div class="panel doc-index-panel">
    <div class="panel-header">
      <div class="panel-title">Expediente — <?= count($lista) ?> documento<?= count($lista) === 1 ? '' : 's' ?></div>
    </div>
    <div class="doc-index-list">
      <?php foreach ($lista as $d):
          $tipoLabel = $tipoLabelFn((string)$d->tipo_documento);
          $archivoUrl = $d->archivo_url ?? null;
          $archivoHref = $archivoUrl ? $this->Url->build($archivoUrl) : null;
      ?>
      <article class="<?= $rowClass($d->estatus ?? null) ?>">
        <div class="doc-index-main">
          <div class="doc-index-tipo"><?= h($tipoLabel) ?></div>
          <div class="doc-index-ref">
            <?php if ($modulo === 'vehiculo' && !empty($d->vehiculo)): ?>
              <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $d->vehiculo->id]) ?>" class="doc-index-link">
                <?= h(strtoupper($d->vehiculo->placas)) ?> — <?= h($d->vehiculo->marca . ' ' . $d->vehiculo->modelo) ?>
              </a>
            <?php elseif ($modulo === 'empresa' && !empty($d->empresa)): ?>
              <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="doc-index-link"><?= h($d->empresa->nombre) ?></a>
            <?php else: ?>
              <span class="doc-index-muted">—</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($d->nombre_archivo)): ?>
            <div class="doc-index-file"><?= h($d->nombre_archivo) ?></div>
          <?php endif; ?>
        </div>
        <div class="doc-index-meta">
          <div class="doc-index-vence">
            <span class="doc-index-meta-label">Vencimiento</span>
            <span class="doc-index-meta-val td-mono">
              <?= $d->fecha_vencimiento ? h($d->fecha_vencimiento->format('d/m/Y')) : '—' ?>
            </span>
          </div>
          <?= $badge($d->estatus ?? null) ?>
        </div>
        <div class="doc-index-actions">
          <?php
            $editUrl = ['controller' => $editController, 'action' => 'edit', $d->id];
            if ($origenQuery) {
                $editUrl['?'] = ['origen' => $origenQuery];
            }
          ?>
          <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build($editUrl) ?>">Editar</a>
          <?php if ($archivoHref): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'ver', '?' => ['r' => $archivoUrl]])) ?>" target="_blank" rel="noopener">Ver archivo</a>
            <a class="btn btn-ghost btn-sm" href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'descargar', '?' => ['r' => $archivoUrl]])) ?>">Descargar</a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
