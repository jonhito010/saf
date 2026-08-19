<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Alerta $alerta
 */
?>

<div style="margin-bottom:12px">
  <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Agregar alerta</div></div>
  <div class="form-panel-body">
    <?= $this->Form->create($alerta) ?>
    <div class="form-grid-2">
      <?= $this->Form->control('nivel_urgencia', ['label' => 'Nivel', 'options' => ['info' => 'Info', 'advertencia' => 'Advertencia', 'critico' => 'Crítico'], 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('tipo_alerta', ['label' => 'Tipo de alerta', 'required' => true]) ?>
      <?= $this->Form->control('entidad_tipo', ['label' => 'Entidad', 'options' => ['empresa' => 'Empresa', 'vehiculo' => 'Vehículo', 'operador' => 'Operador'], 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('entidad_id', ['label' => 'ID de entidad', 'type' => 'number']) ?>
      <?= $this->Form->control('empresa_id', ['label' => 'Empresa (ID)', 'type' => 'number']) ?>
      <?= $this->Form->control('tipo_documento', ['label' => 'Tipo documento', 'required' => false]) ?>
      <?= $this->Form->control('fecha_vencimiento', ['label' => 'Vencimiento', 'empty' => true, 'required' => false]) ?>
      <?= $this->Form->control('resuelto', ['label' => 'Resuelto', 'type' => 'checkbox', 'required' => false]) ?>
      <div style="grid-column:1/-1">
        <?= $this->Form->control('descripcion', ['label' => 'Descripción', 'type' => 'textarea', 'required' => false, 'rows' => 3]) ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
