<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentoEmpresa $documento
 * @var array $empresas
 * @var array<string, string> $tiposDocumento
 * @var string $origenCodigo
 * @var array $urlVolver
 */
?>

<div style="margin-bottom:12px;display:flex;gap:8px;align-items:center">
  <a href="<?= $this->Url->build($urlVolver) ?>" class="btn btn-ghost" style="font-size:12px">← Volver a documentación empresa</a>
  <?= $this->Form->postLink(
      'Eliminar',
      ['action' => 'delete', $documento->id, '?' => ['origen' => $origenCodigo]],
      ['confirm' => '¿Eliminar este documento?', 'class' => 'btn btn-ghost', 'style' => 'font-size:12px'],
  ) ?>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Editar documento</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($documento, ['type' => 'file']) ?>
    <?= $this->Form->hidden('origen', ['value' => $origenCodigo]) ?>
    <?= $this->Form->hidden('return_to', ['value' => 'empresa']) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('empresa_id', ['label' => 'Empresa', 'options' => $empresas, 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('tipo_documento', ['label' => 'Tipo', 'options' => $tiposDocumento, 'empty' => 'Selecciona…', 'required' => true]) ?>
      <?= $this->Form->control('estatus', ['label' => 'Estatus', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido'], 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('fecha_vencimiento', ['label' => 'Fecha de vencimiento', 'empty' => true, 'required' => false]) ?>
      <?php if (!empty($documento->ruta_archivo)): ?>
        <div style="grid-column:1/-1">
          <div class="data-label">Archivo actual</div>
          <a href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'ver', '?' => ['r' => $documento->ruta_archivo]])) ?>" target="_blank" rel="noopener"><?= h($documento->nombre_archivo) ?></a>
        </div>
      <?php endif; ?>
      <?= $this->Form->control('archivo', ['label' => 'Reemplazar archivo', 'type' => 'file', 'required' => false]) ?>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build($urlVolver) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
