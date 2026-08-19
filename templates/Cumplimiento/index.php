<?php
/**
 * @var \App\View\AppView $this
 * @var array $vehiculosCumpl
 * @var array $operadoresCumpl
 * @var iterable $docsVencidos
 * @var iterable $docsOpVencidos
 * @var iterable $docsEmpVencidos
 * @var array<string, array{label: string, descripcion: string, retencion: string}> $requisitosFlota
 * @var string $notaRetencionFlota
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

use App\Model\Table\DocumentosVehiculoTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosEmpresaTable;

$cumplBar = function (int $pct): array {
    if ($pct < 60) return ['fill-red',   'color:var(--red)'];
    if ($pct < 90) return ['fill-amber', 'color:#C87000'];
    return ['fill-teal', 'color:var(--teal-dark)'];
};

$badgeNivel = function (string $estatus): string {
    return match ($estatus) {
        'vencido'    => '<span class="badge badge-red">crítico</span>',
        'por_vencer' => '<span class="badge badge-amber">advertencia</span>',
        'faltante'   => '<span class="badge badge-red">faltante</span>',
        default      => '<span class="badge badge-green">vigente</span>',
    };
};
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Cumplimiento normativo</h1>
    <p>
      Expediente por vehículo: Inspección FM, Inspección E y documentos obligatorios
      <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
    </p>
  </div>
  <div class="veh-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos']) ?>">Materiales peligrosos</a>
    <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'DocumentosAdicionales', 'action' => 'index']) ?>">Documentos adicionales</a>
  </div>
</div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
]) ?>

<div class="panel cumpl-norm-intro" style="margin-bottom:12px">
  <div class="panel-header">
    <div class="panel-title">Requisitos por vehículo</div>
  </div>
  <div style="padding:14px 18px">
    <p class="cumpl-norm-note"><?= h($notaRetencionFlota) ?></p>
    <div class="cumpl-norm-grid">
      <?php foreach ($requisitosFlota as $tipo => $meta): ?>
      <div class="cumpl-norm-card">
        <h3 class="cumpl-norm-card-title"><?= h($meta['label']) ?></h3>
        <p class="cumpl-norm-card-desc"><?= h($meta['descripcion']) ?></p>
        <p class="cumpl-norm-card-ret"><strong>Retención:</strong> <?= h($meta['retencion']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">

  <!-- Cumplimiento vehículos -->
  <div class="panel">
    <div class="panel-header"><div class="panel-title">Cumplimiento por vehículo</div></div>
    <div class="cumpl-list">
      <?php foreach ($vehiculosCumpl as $item):
          [$fillClass, $pctColor] = $cumplBar($item['pct']);
          $v = $item['vehiculo'];
      ?>
      <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $v->id]) ?>" class="cumpl-row-link">
        <div class="cumpl-top">
          <span class="cumpl-name">
            <?= h($v->placas) ?> — <?= h($v->marca . ' ' . $v->modelo) ?>
            <?php if (!empty($vistaConsolidada) && !empty($v->empresa)): ?>
              <span class="veh-fleet-empresa"> · <?= h($v->empresa->nombre) ?></span>
            <?php endif; ?>
          </span>
          <span class="cumpl-pct" style="<?= $pctColor ?>"><?= $item['pct'] ?>%</span>
        </div>
        <div class="cumpl-bar">
          <div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $item['pct'] ?>%"></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cumplimiento operadores -->
  <div class="panel">
    <div class="panel-header"><div class="panel-title">Cumplimiento por operador</div></div>
    <div class="cumpl-list">
      <?php foreach ($operadoresCumpl as $item):
          [$fillClass, $pctColor] = $cumplBar($item['pct']);
          $op = $item['operador'];
      ?>
      <a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $op->id]) ?>" class="cumpl-row-link">
        <div class="cumpl-top">
          <span class="cumpl-name">
            <?= h($op->nombre_completo) ?>
            <?php if (!empty($vistaConsolidada) && !empty($op->empresa)): ?>
              <span class="veh-fleet-empresa"> · <?= h($op->empresa->nombre) ?></span>
            <?php endif; ?>
          </span>
          <span class="cumpl-pct" style="<?= $pctColor ?>"><?= $item['pct'] ?>%</span>
        </div>
        <div class="cumpl-bar">
          <div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $item['pct'] ?>%"></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Tabla de documentos con problemas -->
<div class="panel">
  <div class="panel-header"><div class="panel-title">Documentos vencidos y faltantes</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Entidad</th><th>Tipo</th><th>Documento</th><th>Vencimiento</th><th>Nivel</th></tr>
      </thead>
      <tbody>
        <?php foreach ($docsEmpVencidos as $doc): ?>
        <tr>
          <td><a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="dash-row-link">Empresa</a></td>
          <td><span class="tag">Empresa</span></td>
          <td><?= h(DocumentosEmpresaTable::tipoLabel($doc->tipo_documento)) ?></td>
          <td class="td-mono"><?= $doc->fecha_vencimiento ? h($doc->fecha_vencimiento->format('d/m/Y')) : '—' ?></td>
          <td><?= $badgeNivel($doc->estatus) ?></td>
        </tr>
        <?php endforeach; ?>

        <?php foreach ($docsVencidos as $doc): ?>
        <tr>
          <td class="td-mono">
            <?php if (!empty($doc->vehiculo_id)): ?>
              <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $doc->vehiculo_id]) ?>" class="dash-row-link"><?= h($doc->vehiculo->placas ?? '—') ?></a>
            <?php else: ?>
              <?= h($doc->vehiculo->placas ?? '—') ?>
            <?php endif; ?>
          </td>
          <td><span class="tag">Vehículo</span></td>
          <td><?= h(DocumentosVehiculoTable::tipoLabel($doc->tipo_documento)) ?></td>
          <td class="td-mono"><?= $doc->fecha_vencimiento ? h($doc->fecha_vencimiento->format('d/m/Y')) : '—' ?></td>
          <td><?= $badgeNivel($doc->estatus) ?></td>
        </tr>
        <?php endforeach; ?>

        <?php foreach ($docsOpVencidos as $doc): ?>
        <tr>
          <td>
            <?php if (!empty($doc->operador_id)): ?>
              <a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $doc->operador_id]) ?>" class="dash-row-link"><?= h($doc->operador->nombre_completo ?? '—') ?></a>
            <?php else: ?>
              <?= h($doc->operador->nombre_completo ?? '—') ?>
            <?php endif; ?>
          </td>
          <td><span class="tag">Operador</span></td>
          <td><?= h(DocumentosOperadorTable::tipoLabel($doc->tipo_documento)) ?></td>
          <td class="td-mono"><?= $doc->fecha_vencimiento ? h($doc->fecha_vencimiento->format('d/m/Y')) : '—' ?></td>
          <td><?= $badgeNivel($doc->estatus) ?></td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($docsVencidos->toArray()) && empty($docsOpVencidos->toArray()) && empty($docsEmpVencidos->toArray())): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--green);padding:20px">✓ Toda la documentación está en orden</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
