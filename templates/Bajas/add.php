<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BajaVehicular $baja
 * @var array $vehiculos
 * @var array $motivoOptions
 * @var array $estatusOptions
 */
?>

<div style="margin-bottom:12px">
  <a href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Iniciar trámite de baja</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($baja) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('vehiculo_id', ['label' => 'Vehículo (placas)', 'options' => $vehiculos, 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('motivo_baja', ['label' => 'Motivo', 'options' => $motivoOptions, 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('fecha_baja', ['label' => 'Fecha de baja', 'empty' => true, 'required' => false]) ?>
      <?= $this->Form->control('numero_folio_baja', ['label' => 'Folio SICT', 'required' => false]) ?>
      <?= $this->Form->control('placas_devueltas', ['label' => 'Placas devueltas', 'type' => 'checkbox', 'required' => false]) ?>
      <?= $this->Form->hidden('estatus_tramite', ['value' => 'iniciado']) ?>
      <?= $this->Form->control('notas', ['label' => 'Notas', 'type' => 'textarea', 'required' => false, 'rows' => 3]) ?>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

