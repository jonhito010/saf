<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $vehiculos
 * @var array<int, string> $estatusMap
 * @var array<int, array<string, int>> $resumenMap
 * @var array<string, int> $fleetStats
 * @var array<string, string> $tipoCargaOptions
 * @var string $filtroQ
 * @var string $filtroTipo
 * @var string $filtroDoc
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

use App\Model\Table\DocumentosVehiculoTable;

$lista = is_array($vehiculos) ? $vehiculos : $vehiculos->toArray();
$urlIndex = ['controller' => 'Vehiculos', 'action' => 'index'];

$estatusLabel = fn(string $s): string => match ($s) {
    'vencido' => 'Vencido',
    'por_vencer' => 'Por vencer',
    'sin_docs' => 'Sin documentos',
    default => 'Al día',
};

$estatusClass = fn(string $s): string => match ($s) {
    'vencido' => 'veh-fleet-card--crit',
    'por_vencer' => 'veh-fleet-card--warn',
    'sin_docs' => 'veh-fleet-card--muted',
    default => 'veh-fleet-card--ok',
};

$docDots = function (array $docs): array {
    $tipos = \App\Model\Table\VehiculosTable::documentosRequeridos();
    $out = [];
    foreach ($tipos as $tipo) {
        $est = null;
        foreach ($docs as $doc) {
            if ($doc->tipo_documento === $tipo) {
                $est = $doc->estatus;
                break;
            }
        }
        $out[] = ['tipo' => $tipo, 'estatus' => $est];
    }
    return $out;
};
?>

<div class="veh-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Vehículos</h1>
      <p>
        Control documental por vehículo — seguro, verificación e inspección
        <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
      </p>
    </div>
    <div class="veh-header-actions">
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>">Cumplimiento</a>
      <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'add']) ?>">
        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Registrar vehículo
      </a>
    </div>
  </div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
    'filtroPreserve' => [
        'q' => $filtroQ ?? '',
        'tipo_carga' => $filtroTipo ?? '',
        'doc' => $filtroDoc ?? '',
    ],
]) ?>

  <div class="veh-kpi-grid">
    <div class="veh-kpi">
      <div class="veh-kpi-val"><?= (int)$fleetStats['total'] ?></div>
      <div class="veh-kpi-label">Vehículos activos</div>
    </div>
    <div class="veh-kpi veh-kpi--ok">
      <div class="veh-kpi-val"><?= (int)$fleetStats['vigente'] ?></div>
      <div class="veh-kpi-label">Documentación al día</div>
    </div>
    <div class="veh-kpi veh-kpi--warn">
      <div class="veh-kpi-val"><?= (int)$fleetStats['por_vencer'] ?></div>
      <div class="veh-kpi-label">Por vencer</div>
    </div>
    <div class="veh-kpi veh-kpi--crit">
      <div class="veh-kpi-val"><?= (int)$fleetStats['vencido'] ?></div>
      <div class="veh-kpi-label">Con vencidos</div>
    </div>
    <div class="veh-kpi veh-kpi--muted">
      <div class="veh-kpi-val"><?= (int)$fleetStats['sin_docs'] ?></div>
      <div class="veh-kpi-label">Sin documentos</div>
    </div>
  </div>

  <form class="veh-filters panel" method="get" action="<?= $this->Url->build($urlIndex) ?>">
    <?php if (!empty($filtroEmpresaId)): ?>
      <input type="hidden" name="empresa_id" value="<?= (int)$filtroEmpresaId ?>">
    <?php endif; ?>
    <div class="veh-filters-row">
      <div class="search-wrap veh-search">
        <svg class="search-icon" width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="search" name="q" value="<?= h($filtroQ) ?>" placeholder="Buscar por placa, marca, modelo o serie…" autocomplete="off">
      </div>
      <select name="tipo_carga" class="veh-select" aria-label="Tipo de carga">
        <option value="">Todos los tipos de carga</option>
        <?php foreach ($tipoCargaOptions as $key => $label): ?>
          <option value="<?= h($key) ?>"<?= $filtroTipo === $key ? ' selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="doc" class="veh-select" aria-label="Estatus documental">
        <option value="">Todo estatus documental</option>
        <option value="vigente"<?= $filtroDoc === 'vigente' ? ' selected' : '' ?>>Al día</option>
        <option value="por_vencer"<?= $filtroDoc === 'por_vencer' ? ' selected' : '' ?>>Por vencer</option>
        <option value="vencido"<?= $filtroDoc === 'vencido' ? ' selected' : '' ?>>Vencido</option>
        <option value="sin_docs"<?= $filtroDoc === 'sin_docs' ? ' selected' : '' ?>>Sin documentos</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
      <?php if ($filtroQ !== '' || $filtroTipo !== '' || $filtroDoc !== ''): ?>
        <a href="<?= $this->Url->build($urlIndex) ?>" class="btn btn-ghost btn-sm">Limpiar</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($lista)): ?>
  <div class="panel veh-empty">
    <div class="veh-empty-icon" aria-hidden="true">🚛</div>
    <h2>No hay vehículos que coincidan</h2>
    <p>Registra un vehículo o ajusta los filtros de búsqueda.</p>
    <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'add']) ?>">Registrar vehículo</a>
  </div>
  <?php else:
    $porEmpresa = [];
    foreach ($lista as $v) {
        $eid = (int)($v->empresa_id ?? 0);
        if (!isset($porEmpresa[$eid])) {
            $porEmpresa[$eid] = [
                'nombre' => (string)($v->empresa->nombre ?? ('Empresa #' . $eid)),
                'vehiculos' => [],
            ];
        }
        $porEmpresa[$eid]['vehiculos'][] = $v;
    }
    uasort(
        $porEmpresa,
        static fn(array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']),
    );
    $mostrarGrupo = count($porEmpresa) > 1 || !empty($vistaConsolidada);
  ?>
    <?php foreach ($porEmpresa as $grupo): ?>
      <?php if ($mostrarGrupo): ?>
      <div class="panel" style="margin-bottom:14px">
        <div class="panel-header">
          <div class="panel-title"><?= h($grupo['nombre']) ?></div>
          <div style="color:var(--text3);font-size:13px">
            <?= count($grupo['vehiculos']) ?> vehículo<?= count($grupo['vehiculos']) === 1 ? '' : 's' ?>
          </div>
        </div>
        <div style="padding:14px 16px">
      <?php endif; ?>
          <div class="veh-fleet-grid">
            <?php foreach ($grupo['vehiculos'] as $v):
                $id = (int)$v->id;
                $worst = $estatusMap[$id] ?? 'vigente';
                $res = $resumenMap[$id] ?? ['total' => 0, 'pct' => 0];
                if ($res['total'] === 0) {
                    $worst = 'sin_docs';
                }
                $viewUrl = $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $id]);
                $docs = $v->documentos_vehiculo ?? [];
            ?>
            <a href="<?= $viewUrl ?>" class="veh-fleet-card <?= $estatusClass($worst) ?>">
              <div class="veh-fleet-card-top">
                <div class="veh-plate"><?= h(strtoupper($v->placas)) ?></div>
                <span class="badge <?= match ($worst) {
                    'vencido' => 'badge-red',
                    'por_vencer' => 'badge-amber',
                    'sin_docs' => 'badge-gray',
                    default => 'badge-green',
                } ?>"><?= h($estatusLabel($worst)) ?></span>
              </div>
              <div class="veh-fleet-title"><?= h($v->marca . ' ' . $v->modelo) ?></div>
              <div class="veh-fleet-meta">
                <?= h((string)$v->anio) ?> · <?= h($v->tipo_carga_label) ?>
              </div>
              <?php if ($res['total'] > 0): ?>
              <div class="veh-fleet-cumpl">
                <div class="veh-fleet-cumpl-top">
                  <span>Cumplimiento</span>
                  <span class="veh-fleet-pct"><?= (int)$res['pct'] ?>%</span>
                </div>
                <div class="cumpl-bar"><div class="cumpl-fill <?= $res['pct'] < 60 ? 'fill-red' : ($res['pct'] < 90 ? 'fill-amber' : 'fill-teal') ?>" style="width:<?= (int)$res['pct'] ?>%"></div></div>
              </div>
              <?php endif; ?>
              <div class="veh-doc-dots" aria-label="Estado de documentos clave">
                <?php foreach ($docDots(is_array($docs) ? $docs : iterator_to_array($docs)) as $dot):
                    $dotClass = match ($dot['estatus']) {
                        'vencido' => 'doc-dot doc-dot--red',
                        'por_vencer' => 'doc-dot doc-dot--amber',
                        'vigente' => 'doc-dot doc-dot--green',
                        default => 'doc-dot doc-dot--gray',
                    };
                    $title = DocumentosVehiculoTable::tipoLabel($dot['tipo']);
                ?>
                <span class="<?= $dotClass ?>" title="<?= h($title) ?>"></span>
                <?php endforeach; ?>
              </div>
              <div class="veh-fleet-footer">
                <span class="veh-fleet-link">Ver expediente →</span>
                <?php if ((int)($res['vencido'] ?? 0) > 0): ?>
                  <span class="veh-fleet-alert"><?= (int)$res['vencido'] ?> vencido<?= $res['vencido'] > 1 ? 's' : '' ?></span>
                <?php endif; ?>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
      <?php if ($mostrarGrupo): ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
