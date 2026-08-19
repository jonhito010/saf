<?php
/**
 * Clientes — alta
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Nuevo cliente</h1>
    <p>Receptor para CFDI.</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Datos del cliente</div>
  </div>
  <div class="empresa-panel-body">
    <?= $this->Form->create($cliente) ?>
    <div class="empresa-form-grid">
      <?= $this->Form->control('nombre', ['label' => 'Nombre / Razón social', 'required' => true]) ?>
      <?= $this->Form->control('rfc', ['label' => 'RFC', 'required' => false, 'maxlength' => 13, 'oninput' => 'this.value=this.value.toUpperCase()']) ?>
      <?= $this->Form->control('regimenFiscal', ['label' => 'Régimen fiscal (SAT)', 'required' => false, 'maxlength' => 5, 'placeholder' => 'Ej. 601']) ?>
      <?= $this->Form->control('usoCFDI', ['label' => 'Uso CFDI', 'required' => false, 'maxlength' => 5, 'placeholder' => 'Ej. G03']) ?>
      <?= $this->Form->control('domicilioFiscal', ['label' => 'Domicilio fiscal (CP)', 'required' => false, 'maxlength' => 10, 'placeholder' => 'Ej. 64500']) ?>
      <?= $this->Form->control('correo', ['label' => 'Correo', 'required' => false, 'type' => 'email']) ?>
      <div class="field-span-2">
        <?= $this->Form->control('direccionCompleta', ['label' => 'Dirección completa', 'required' => false, 'maxlength' => 512]) ?>
      </div>
    </div>
    <div class="empresa-form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

