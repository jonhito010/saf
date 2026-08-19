<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BajaVehicular $baja
 * @var array $vehiculos
 * @var array $motivoOptions
 * @var array $estatusOptions
 * @var array $pasos
 */

$estatusTramiteMap = [
    'iniciado'         => 1,
    'pendiente_placas' => 2,
    'placas_devueltas' => 3,
    'tramite_baja'     => 4,
    'completado'       => 5,
];
$pasoNum = $estatusTramiteMap[$baja->estatus_tramite] ?? 1;
$completado = $baja->estatus_tramite === 'completado';
$origenDocs = !empty($baja->vehiculo_id)
    ? 'vehiculo:' . (int)$baja->vehiculo_id . ':hist'
    : null;
?>

<div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
  <a href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
  <?= $this->Form->postLink('Eliminar', ['action' => 'delete', $baja->id], ['confirm' => '¿Eliminar este trámite?', 'class' => 'btn btn-ghost', 'style' => 'font-size:12px']) ?>
  <?php if (!$completado): ?>
    <?= $this->Form->postLink('Avanzar al siguiente paso →', ['action' => 'avanzarPaso', $baja->id], ['class' => 'btn btn-teal', 'style' => 'font-size:12px']) ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Trámite de baja — paso <?= (int)$pasoNum ?> de <?= count($pasos) ?></div>
    <?php if ($completado): ?><span class="badge badge-green">completado</span><?php else: ?><span class="badge badge-amber">en proceso</span><?php endif; ?>
  </div>
  <div style="padding:20px 24px">
    <?= $this->element('baja_stepper', compact('baja', 'pasos')) ?>

    <?php if ($baja->estatus_tramite === 'pendiente_placas' && $origenDocs): ?>
    <div style="background:var(--amber-bg);border:1px solid #E8C060;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px">
      <strong>Paso 2 — Documentos:</strong> adjunta la documentación requerida para el trámite de baja.
      <a class="btn btn-ghost btn-sm" style="margin-left:8px" href="<?= $this->Url->build(['controller' => 'DocumentosVehiculo', 'action' => 'add', '?' => ['vehiculo_id' => $baja->vehiculo_id, 'origen' => $origenDocs]]) ?>">+ Subir documento</a>
      <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $baja->vehiculo_id, '?' => ['tab' => 'hist']]) ?>">Ver expediente del vehículo</a>
    </div>
    <?php endif; ?>

    <?= $this->Form->create($baja) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('vehiculo_id', ['label' => 'Vehículo (placas)', 'options' => $vehiculos, 'empty' => 'Selecciona…', 'disabled' => $completado]) ?>
      <?= $this->Form->control('motivo_baja', ['label' => 'Motivo', 'options' => $motivoOptions, 'empty' => 'Selecciona…', 'disabled' => $completado]) ?>
      <?= $this->Form->control('fecha_baja', ['label' => 'Fecha de baja', 'empty' => true, 'required' => false, 'disabled' => $completado]) ?>
      <?= $this->Form->control('numero_folio_baja', ['label' => 'Folio SICT', 'required' => false, 'disabled' => $completado]) ?>
      <?= $this->Form->control('placas_devueltas', ['label' => 'Placas devueltas', 'type' => 'checkbox', 'required' => false, 'disabled' => $completado]) ?>
      <?php if ($completado): ?>
        <?= $this->Form->hidden('vehiculo_id') ?>
        <?= $this->Form->hidden('motivo_baja') ?>
        <?= $this->Form->hidden('estatus_tramite') ?>
      <?php else: ?>
        <?= $this->Form->control('estatus_tramite', ['label' => 'Estatus del trámite', 'options' => $estatusOptions]) ?>
      <?php endif; ?>
      <?= $this->Form->control('notas', ['label' => 'Notas', 'type' => 'textarea', 'required' => false, 'rows' => 3, 'disabled' => $completado]) ?>
    </div>
    <?php if (!$completado): ?>
    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'index']) ?>">Cancelar</a>
    </div>
    <?php endif; ?>
    <?= $this->Form->end() ?>
  </div>
</div>
