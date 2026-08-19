<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentoEmpresa $documento
 * @var array $empresas
 * @var bool $empresaFija
 * @var array<string, string> $tiposDocumento
 * @var bool $returnToEmpresa
 * @var string $origenCodigo
 * @var array $urlVolver
 */
use App\Utility\DocumentoUtil;
$maxUploadLbl = DocumentoUtil::limiteUploadHumano();
?>

<div style="margin-bottom:12px">
  <a href="<?= $this->Url->build($urlVolver) ?>" class="btn btn-ghost" style="font-size:12px">← Volver a documentación empresa</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Agregar documento</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($documento, ['type' => 'file']) ?>
    <?= $this->Form->hidden('origen', ['value' => $origenCodigo]) ?>
    <?php if (!empty($returnToEmpresa)): ?>
      <?= $this->Form->hidden('return_to', ['value' => 'empresa']) ?>
    <?php endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?php if (!empty($empresaFija) && $documento->empresa_id): ?>
        <?= $this->Form->hidden('empresa_id', ['value' => $documento->empresa_id]) ?>
        <div style="grid-column:1/-1">
          <div class="data-label" style="margin-bottom:4px;color:var(--text3);font-size:12px">Empresa</div>
          <div style="font-size:13px;font-weight:500"><?= h($empresas[$documento->empresa_id] ?? '') ?></div>
        </div>
      <?php else: ?>
        <?= $this->Form->control('empresa_id', ['label' => 'Empresa', 'options' => $empresas, 'empty' => 'Selecciona…']) ?>
      <?php endif; ?>
      <?= $this->Form->control('tipo_documento', ['label' => 'Tipo', 'options' => $tiposDocumento, 'empty' => 'Selecciona…', 'required' => true]) ?>
      <?= $this->Form->control('estatus', ['label' => 'Estatus', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido'], 'empty' => 'Selecciona…']) ?>
      <?= $this->Form->control('fecha_vencimiento', ['label' => 'Fecha de vencimiento', 'empty' => true, 'required' => false]) ?>
      <?= $this->Form->control('archivo', ['label' => 'Archivo (PDF, imagen o Word, máx. ' . $maxUploadLbl . ')', 'type' => 'file', 'required' => true]) ?>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build($urlVolver) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
