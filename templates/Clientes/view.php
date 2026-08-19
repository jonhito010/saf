<?php
/**
 * Clientes — detalle
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Cliente</h1>
    <p><?= h($cliente->nombre ?? '') ?></p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver</a>
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'edit', $cliente->id]) ?>">Editar</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Datos</div>
  </div>
  <div class="empresa-panel-body" style="display:flex;flex-direction:column;gap:12px">
    <div class="data-row">
      <div class="data-label">RFC</div>
      <div class="data-value td-mono"><?= h($cliente->rfc ?? '—') ?></div>
    </div>
    <div class="data-row">
      <div class="data-label">Régimen fiscal</div>
      <div class="data-value td-mono"><?= h($cliente->regimenFiscal ?? '—') ?></div>
    </div>
    <div class="data-row">
      <div class="data-label">Uso CFDI</div>
      <div class="data-value td-mono"><?= h($cliente->usoCFDI ?? '—') ?></div>
    </div>
    <div class="data-row">
      <div class="data-label">Domicilio fiscal (CP)</div>
      <div class="data-value td-mono"><?= h($cliente->domicilioFiscal ?? '—') ?></div>
    </div>
    <div class="data-row">
      <div class="data-label">Correo</div>
      <div class="data-value"><?= h($cliente->correo ?? '—') ?></div>
    </div>
    <div class="data-row">
      <div class="data-label">Dirección completa</div>
      <div class="data-value"><?= nl2br(h($cliente->direccionCompleta ?? '—')) ?></div>
    </div>
  </div>
</div>

