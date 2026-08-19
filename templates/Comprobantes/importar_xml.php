<?php
/**
 * Importar XML CFDI ya timbrado.
 *
 * @var \App\View\AppView $this
 */
?>
<link rel="stylesheet" href="<?= $this->Url->build('/css/comprobantes-index.css') ?>">

<div class="fac-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Importar XML timbrado</h1>
      <p>Registra en el sistema un CFDI que ya fue timbrado (por ejemplo, emitido en otro sistema o recuperado del SAT).</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver al listado</a>
      <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'generar']) ?>">Generar Carta Porte</a>
    </div>
  </div>

  <div class="panel" style="max-width:640px">
    <div class="panel-header">
      <div class="panel-title">Archivo XML</div>
    </div>
    <form method="post" enctype="multipart/form-data" style="padding:18px">
      <?php $csrf = $this->request->getAttribute('csrfToken'); ?>
      <?php if ($csrf) : ?>
      <input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
      <?php endif; ?>

      <p style="font-size:13px;color:var(--text2);margin-bottom:14px">
        El archivo debe contener el timbre fiscal digital (UUID). No se volverá a timbrar; solo se guardará el XML,
        se generará el PDF y quedará disponible en el listado.
      </p>

      <div style="margin-bottom:14px">
        <label for="archivo_xml" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px">XML del comprobante</label>
        <input type="file" id="archivo_xml" name="archivo_xml" accept=".xml,application/xml,text/xml" required>
      </div>

      <label class="cfdi-check-optional" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:13px">
        <input type="hidden" name="mostrar_carta_porte_pdf" value="0">
        <input type="checkbox" name="mostrar_carta_porte_pdf" value="1" checked>
        Incluir detalle del complemento Carta Porte en el PDF
      </label>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-file-import" aria-hidden="true"></i> Importar y generar PDF
      </button>
    </form>
  </div>
</div>
