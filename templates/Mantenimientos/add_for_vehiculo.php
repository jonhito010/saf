<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mantenimiento $mantenimiento
 * @var array $vehiculos
 * @var int $vehiculoId
 */
?>

<div style="margin-bottom:12px">
  <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $vehiculoId]) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Agregar mantenimiento</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($mantenimiento) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('vehiculo_id', ['label' => 'Vehículo (placas)', 'options' => $vehiculos, 'disabled' => true]) ?>
      <?= $this->Form->control('fecha', ['label' => 'Fecha']) ?>
      <?= $this->Form->control('tipo_servicio', ['label' => 'Tipo de servicio', 'placeholder' => 'Preventivo, correctivo, etc.']) ?>
      <?= $this->Form->control('kilometraje', ['label' => 'Kilometraje', 'required' => false]) ?>
      <?= $this->Form->control('taller', ['label' => 'Taller', 'required' => false]) ?>
      <?= $this->Form->control('costo', ['label' => 'Costo', 'required' => false]) ?>
      <div style="grid-column:1/-1">
        <?= $this->Form->control('descripcion', ['label' => 'Descripción', 'type' => 'textarea', 'required' => false, 'rows' => 3]) ?>
      </div>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $vehiculoId]) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

