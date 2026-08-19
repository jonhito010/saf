<?php
/**
 * Vista previa del XML del comprobante (iframe)
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comprobante $comprobante
 */
$this->assign('title', 'XML — ' . (string)($comprobante->uuid ?? $comprobante->id));
$xmlSrc = $this->Url->build(['action' => 'verXml', $comprobante->id]);
$descargaXml = $this->Url->build(['action' => 'xml', $comprobante->id]);
$vistaPdf = $this->Url->build(['action' => 'vistaPdf', $comprobante->id]);
?>
<link rel="stylesheet" href="<?= $this->Url->build('/css/comprobantes.css') ?>">

<div class="cp cp-pdf-view">
  <header class="cp-header cp-pdf-view__header">
    <div class="cp-header-left">
      <h1 class="cp-title">Vista previa del XML</h1>
      <p class="cp-sub">
        UUID <?= h((string)($comprobante->uuid ?? '—')) ?>
        · <?= h((string)($comprobante->serie ?? '')) ?> / <?= h((string)($comprobante->folio ?? '')) ?>
      </p>
    </div>
    <div class="cp-actions">
      <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="cp-btn cp-btn--ghost cp-btn--sm">
        <i class="fa-solid fa-list"></i> Listado
      </a>
      <a href="<?= $this->Url->build(['action' => 'view', $comprobante->id]) ?>" class="cp-btn cp-btn--ghost cp-btn--sm">
        <i class="fa-solid fa-file-lines"></i> Detalle
      </a>
      <a href="<?= h($vistaPdf) ?>" target="_blank" rel="noopener noreferrer" class="cp-btn cp-btn--danger cp-btn--sm">
        <i class="fa-solid fa-file-pdf"></i> Vista PDF
      </a>
      <a href="<?= h($descargaXml) ?>" class="cp-btn cp-btn--primary cp-btn--sm">
        <i class="fa-solid fa-download"></i> Descargar XML
      </a>
    </div>
  </header>

  <div class="cp-card cp-pdf-view__card">
    <div class="cp-pdf-view__frame-wrap cp-pdf-view__frame-wrap--xml">
      <iframe
        class="cp-pdf-view__frame"
        title="XML del comprobante"
        src="<?= h($xmlSrc) ?>"
      ></iframe>
    </div>
  </div>
</div>
