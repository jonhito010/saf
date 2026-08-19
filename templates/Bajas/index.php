<?php
/**
 * @var \App\View\AppView $this
 * @var array $bajasEnProceso
 * @var array $historial
 * @var array $pasos
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

$estatusTramiteMap = [
    'iniciado'         => 1,
    'pendiente_placas' => 2,
    'placas_devueltas' => 3,
    'tramite_baja'     => 4,
    'completado'       => 5,
];
$vistaConsolidada = $vistaConsolidada ?? false;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Bajas vehiculares</h1>
    <p>
      Control de trámites de baja y reposiciones
      <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
    </p>
  </div>
  <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'add']) ?>">+ Iniciar trámite de baja</a>
</div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada,
    'alcanceLabel' => $alcanceLabel ?? '',
]) ?>

<!-- Trámites en proceso -->
<?php foreach ($bajasEnProceso as $baja):
    $pasoNum = $estatusTramiteMap[$baja->estatus_tramite] ?? 1;
?>
<div class="panel" style="margin-bottom:14px">
  <div class="panel-header">
    <div class="panel-title">
      Trámite activo —
      <span class="td-mono" style="font-weight:500"><?= h($baja->vehiculo->placas ?? '—') ?></span>
      · <?= ucfirst(h($baja->motivo_baja_label)) ?><?php if (($baja->motivo_baja ?? '') === 'reemplazo'): ?> → Reposición<?php endif; ?>
      <?php if ($vistaConsolidada && !empty($baja->empresa)): ?>
        · <span style="font-weight:400;color:var(--text3)"><?= h($baja->empresa->nombre ?? $baja->empresa->razon_social ?? '') ?></span>
      <?php endif; ?>
    </div>
    <span class="badge badge-amber">en proceso</span>
  </div>
  <div style="padding:20px 24px">
    <div class="stepper">
      <?php foreach ($pasos as $num => $paso):
          $isDone   = $num < $pasoNum;
          $isActive = $num === $pasoNum;
          $class    = $isDone ? 'step done' : ($isActive ? 'step active' : 'step');
      ?>
      <div class="<?= $class ?>">
        <div class="step-circle"><?= $isDone ? '✓' : $num ?></div>
        <div class="step-label"><?= nl2br(h($paso['label'])) ?></div>
      </div>
      <?php if ($num < count($pasos)): ?>
      <div class="step-connector" style="<?= $isDone ? 'background:var(--teal)' : '' ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($baja->notas): ?>
    <div style="background:var(--amber-bg);border:1px solid #E8C060;border-radius:8px;padding:11px 14px;font-size:13px;color:var(--amber)">
      <strong>Acción pendiente:</strong> <?= h($baja->notas) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($bajasEnProceso)): ?>
<div class="panel" style="padding:24px;text-align:center;color:var(--text3);margin-bottom:14px">
  No hay trámites de baja en proceso.
  <a class="btn btn-teal" style="margin-left:12px;font-size:12px" href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'add']) ?>">+ Iniciar trámite</a>
</div>
<?php endif; ?>

<!-- Historial: solo completadas, acotadas a las empresas del filtro/alcance -->
<div class="panel">
  <div class="panel-header">
    <div class="panel-title">
      Historial de bajas
      <?php if (!empty($alcanceLabel)): ?>
        <span style="font-weight:400;color:var(--text3);font-size:12px"> · <?= h($alcanceLabel) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Empresa</th>
          <th>Vehículo</th>
          <th>Motivo</th>
          <th>Fecha baja</th>
          <th>Folio SICT</th>
          <th>Placas devueltas</th>
          <th>Estatus</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historial as $baja): ?>
        <tr>
          <td><?= h($baja->empresa->nombre ?? $baja->empresa->razon_social ?? '—') ?></td>
          <td class="td-mono"><?= h($baja->vehiculo->placas ?? '—') ?></td>
          <td><?= ucfirst(h($baja->motivo_baja_label)) ?></td>
          <td class="td-mono"><?= $baja->fecha_baja ? h($baja->fecha_baja->format('d/m/Y')) : '—' ?></td>
          <td class="td-mono"><?= $baja->numero_folio_baja ? h($baja->numero_folio_baja) : '—' ?></td>
          <td>
            <?php if ($baja->placas_devueltas): ?>
              <span class="badge badge-green">sí</span>
            <?php else: ?>
              <span class="badge badge-amber">pendiente</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-green">completado</span></td>
          <td>
            <a href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'edit', $baja->id]) ?>" class="panel-action" style="font-size:12px">Editar →</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($historial)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text3);padding:20px">Sin bajas completadas para las empresas seleccionadas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
