<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentoEmpresa $documento
 * @var string $empresaNombre
 * @var array<string, string> $tiposDocumento
 * @var string $origenCodigo
 * @var array $urlVolver
 */
use App\Utility\DocumentoUtil;
$maxUploadLbl = DocumentoUtil::limiteUploadHumano();
?>
<div class="veh-page">
  <div class="veh-toolbar">
    <a href="<?= $this->Url->build($urlVolver) ?>" class="btn btn-ghost btn-sm">← Volver</a>
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-ghost btn-sm">Listado adicionales</a>
  </div>
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">Agregar documento adicional</div>
      <span class="veh-form-hint"><?= h($empresaNombre) ?></span>
    </div>
    <div class="veh-form-body">
      <?= $this->Form->create($documento, ['type' => 'file']) ?>
      <?= $this->Form->hidden('origen', ['value' => $origenCodigo]) ?>
      <div class="veh-form-grid">
        <?= $this->Form->hidden('empresa_id', ['value' => $documento->empresa_id]) ?>
        <div class="field-span-2 oper-form-empresa">
          <span class="oper-form-empresa-label">Empresa</span>
          <span class="oper-form-empresa-name"><?= h($empresaNombre) ?></span>
        </div>
        <?= $this->Form->control('tipo_documento', ['label' => 'Tipo de documento', 'options' => $tiposDocumento, 'empty' => 'Selecciona…', 'required' => true]) ?>
        <?= $this->Form->control('estatus', ['label' => 'Estatus', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido']]) ?>
        <?= $this->Form->control('fecha_vencimiento', ['label' => 'Fecha de vencimiento / periodo', 'empty' => true, 'required' => false]) ?>
        <?= $this->Form->control('archivo', ['label' => 'Archivo (PDF, imagen, Word o Excel, máx. ' . $maxUploadLbl . ')', 'type' => 'file', 'required' => true, 'class' => 'field-span-2']) ?>
      </div>
      <div class="veh-form-actions" style="position:static;box-shadow:none;border:none;padding:16px 0 0">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-ghost" href="<?= $this->Url->build($urlVolver) ?>">Cancelar</a>
      </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
