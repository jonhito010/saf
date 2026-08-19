<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentoEmpresa $documento
 * @var array<string, string> $tiposDocumento
 * @var string $origenCodigo
 * @var array $urlVolver
 */
?>
<div class="veh-page">
  <div class="veh-toolbar">
    <a href="<?= $this->Url->build($urlVolver) ?>" class="btn btn-ghost btn-sm">← Volver</a>
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-ghost btn-sm">Listado adicionales</a>
    <?= $this->Form->postLink(
        'Eliminar',
        ['action' => 'delete', $documento->id, '?' => ['origen' => $origenCodigo]],
        ['confirm' => '¿Eliminar este documento adicional?', 'class' => 'btn btn-ghost btn-sm'],
    ) ?>
  </div>
  <div class="panel">
    <div class="panel-header"><div class="panel-title">Editar documento adicional</div></div>
    <div class="veh-form-body">
      <?= $this->Form->create($documento, ['type' => 'file']) ?>
      <?= $this->Form->hidden('origen', ['value' => $origenCodigo]) ?>
      <div class="veh-form-grid">
        <?= $this->Form->control('tipo_documento', ['label' => 'Tipo', 'options' => $tiposDocumento]) ?>
        <?= $this->Form->control('estatus', ['label' => 'Estatus', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido']]) ?>
        <?= $this->Form->control('fecha_vencimiento', ['label' => 'Fecha de vencimiento / periodo', 'empty' => true]) ?>
        <?php if ($documento->ruta_archivo): ?>
          <div class="field-span-2">
            <div class="data-label">Archivo actual</div>
            <a href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'ver', '?' => ['r' => $documento->ruta_archivo]])) ?>" target="_blank" rel="noopener"><?= h($documento->nombre_archivo) ?></a>
            · <a href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'descargar', '?' => ['r' => $documento->ruta_archivo]])) ?>">Descargar</a>
          </div>
        <?php endif; ?>
        <?= $this->Form->control('archivo', ['label' => 'Reemplazar archivo', 'type' => 'file', 'required' => false]) ?>
      </div>
      <div class="veh-form-actions" style="position:static;box-shadow:none;border:none;padding:16px 0 0">
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <a class="btn btn-ghost" href="<?= $this->Url->build($urlVolver) ?>">Cancelar</a>
      </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
