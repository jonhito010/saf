<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentoOperador $documento
 * @var array $operadores
 * @var array<string, string> $tiposDocumento
 */
?>

<div style="margin-bottom:12px;display:flex;gap:8px;align-items:center">
  <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
  <?= $this->Form->postLink('Eliminar', ['action' => 'delete', $documento->id], ['confirm' => '¿Eliminar este documento?', 'class' => 'btn btn-ghost', 'style' => 'font-size:12px']) ?>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Editar documento</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($documento) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('operador_id', ['label' => 'Operador', 'options' => $operadores, 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('tipo_documento', [
          'label' => 'Tipo de documento',
          'options' => $tiposDocumento,
          'disabled' => true,
          'value' => $documento->tipo_documento,
      ]) ?>
      <?= $this->Form->hidden('tipo_documento', ['value' => $documento->tipo_documento]) ?>
      <?= $this->Form->control('estatus', ['label' => 'Estatus', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido'], 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('fecha_vencimiento', ['label' => 'Fecha de vencimiento', 'empty' => true, 'required' => false]) ?>
      <?= $this->Form->control('archivo_url', ['label' => 'Archivo (URL/ruta)', 'required' => false]) ?>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

