<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Operador $operador
 * @var array<string, \App\Model\Entity\DocumentoOperador> $docsPorTipo
 * @var bool $esAlta
 */

use App\Model\Table\DocumentosOperadorTable;
use App\Utility\DocumentoUtil;

$maxUploadLbl = DocumentoUtil::limiteUploadHumano();

$tiposForm = DocumentosOperadorTable::tiposFormularioOperador();
$tiposObligatorios = DocumentosOperadorTable::tiposObligatoriosFormulario();
$fotoUrl = !empty($operador->foto_url) ? $this->Url->build($operador->foto_url) : null;
$esAlta = $esAlta ?? $operador->isNew();

$docIcon = fn(string $tipo): string => match ($tipo) {
    'ine' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M7 18c0-2.8 2.2-5 5-5s5 2.2 5 5"/></svg>',
    'licencia' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h4M7 13h10"/></svg>',
    default => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 10l8-6 8 6v10a1 1 0 01-1 1H5a1 1 0 01-1-1V10z"/><path d="M9 21v-8h6v8"/></svg>',
};
?>

<section class="oper-form-card oper-form-card--expediente" id="oper-expediente">
  <div class="oper-form-card-head">
    <span class="oper-form-card-num">2</span>
    <div>
      <h2 class="oper-form-card-title">Foto e identificación</h2>
      <p class="oper-form-card-desc">Licencia federal, INE y comprobante de domicilio son obligatorios. Máx. <?= h($maxUploadLbl) ?> por archivo.</p>
    </div>
  </div>
  <div class="oper-form-card-body">
    <div class="oper-archivos-grid">
      <div class="oper-upload-card oper-upload-card--foto">
        <div class="oper-upload-card-header">
          <span class="oper-upload-icon oper-upload-icon--foto" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="10" r="4"/><path d="M5 20c0-4 3-7 7-7s7 3 7 7"/></svg>
          </span>
          <div>
            <div class="oper-upload-label">Foto del operador</div>
            <span class="oper-upload-tag">Recomendado</span>
          </div>
        </div>
        <div class="oper-upload-foto-row">
          <div class="oper-foto-drop<?= $fotoUrl ? ' oper-foto-drop--has' : '' ?>">
            <?php if ($fotoUrl): ?>
              <div class="oper-foto-preview">
                <img src="<?= h($fotoUrl) ?>" alt="Foto actual" width="120" height="120">
              </div>
            <?php else: ?>
              <div class="oper-foto-placeholder">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" opacity=".4"><circle cx="12" cy="10" r="4"/><path d="M5 20c0-4 3-7 7-7s7 3 7 7"/></svg>
                <span>JPG o PNG</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="oper-upload-fields">
            <?php if ($fotoUrl): ?>
              <p class="oper-upload-current"><a href="<?= h($fotoUrl) ?>" target="_blank" rel="noopener">Ver foto actual</a></p>
            <?php endif; ?>
            <?= $this->Form->control('foto', [
                'label' => $fotoUrl ? 'Reemplazar foto' : 'Seleccionar foto',
                'type' => 'file',
                'accept' => 'image/jpeg,image/png,image/webp',
                'required' => false,
            ]) ?>
          </div>
        </div>
      </div>

      <?php foreach ($tiposForm as $tipo):
          $doc = $docsPorTipo[$tipo] ?? null;
          $campo = match ($tipo) {
              'ine' => 'archivo_ine',
              'licencia' => 'archivo_licencia',
              'curso_capacitacion' => 'archivo_capacitacion',
              default => 'archivo_comprobante_domicilio',
          };
          $campoFecha = match ($tipo) {
              'ine' => 'fecha_vencimiento_ine',
              'licencia' => 'fecha_vencimiento_licencia',
              'curso_capacitacion' => 'fecha_vencimiento_capacitacion',
              default => 'fecha_vencimiento_comprobante_domicilio',
          };
          $esCapacitacion = $tipo === 'curso_capacitacion';
          $archivoHref = ($doc && $doc->archivo_url) ? $this->Url->build($doc->archivo_url) : null;
          $fechaVal = $doc && $doc->fecha_vencimiento
              ? $doc->fecha_vencimiento->format('Y-m-d')
              : '';
          $esLicencia = $tipo === 'licencia';
          $esObligatorio = in_array($tipo, $tiposObligatorios, true);
          $archivoObligatorio = $esObligatorio && !$doc;
          $cardClass = 'oper-upload-card' . ($esLicencia ? ' oper-upload-card--licencia' : '');
      ?>
      <div class="<?= $cardClass ?>">
        <div class="oper-upload-card-header">
          <span class="oper-upload-icon<?= $esLicencia ? ' oper-upload-icon--licencia' : '' ?>" aria-hidden="true"><?= $docIcon($tipo) ?></span>
          <div>
            <div class="oper-upload-label">
              <?= h(DocumentosOperadorTable::tipoLabel($tipo)) ?><?php if ($esObligatorio): ?> <span class="req-star" aria-hidden="true">*</span><?php endif; ?>
            </div>
            <?php if ($esObligatorio): ?>
              <span class="oper-upload-tag oper-upload-tag--req">Obligatorio</span>
            <?php elseif ($esLicencia): ?>
              <span class="oper-upload-tag oper-upload-tag--req">Requiere vencimiento</span>
            <?php elseif ($esCapacitacion): ?>
              <span class="oper-upload-tag oper-upload-tag--req">Constancia vigente (MP)</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($doc): ?>
          <p class="oper-upload-current">
            <span class="oper-upload-status oper-upload-status--ok">Cargado</span>
            <strong><?= h($doc->nombre_archivo) ?></strong>
            <?php if ($archivoHref): ?>
              — <a href="<?= h($archivoHref) ?>" target="_blank" rel="noopener">Ver archivo</a>
            <?php endif; ?>
          </p>
        <?php else: ?>
          <p class="oper-upload-current oper-upload-missing">
            <span class="oper-upload-status"><?= $esObligatorio ? 'Obligatorio' : ($esAlta ? 'Pendiente' : 'Sin archivo') ?></span>
          </p>
        <?php endif; ?>
        <div class="oper-upload-drop">
          <?= $this->Form->control($campo, [
              'label' => $doc
                  ? 'Reemplazar archivo'
                  : ($archivoObligatorio
                      ? ['text' => 'Subir archivo <span class="req-star" aria-hidden="true">*</span>', 'escape' => false]
                      : 'Subir archivo'),
              'type' => 'file',
              'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
              'required' => $archivoObligatorio,
          ]) ?>
        </div>
        <?php
          $labelFecha = $tipo === 'comprobante_domicilio'
              ? 'Fecha del comprobante (opcional)'
              : 'Fecha de vencimiento';
          if ($esLicencia) {
              $labelFecha = ['text' => 'Fecha de vencimiento de la licencia <span class="req" aria-hidden="true">*</span>', 'escape' => false];
          } elseif ($esCapacitacion) {
              $labelFecha = ['text' => 'Vigencia de la capacitación <span class="req-star" aria-hidden="true">*</span>', 'escape' => false];
          }
        ?>
        <?= $this->Form->control($campoFecha, [
            'label' => $labelFecha,
            'type' => 'date',
            'value' => $fechaVal,
            'required' => $esLicencia || $esCapacitacion,
            'class' => $esLicencia ? 'oper-fecha-licencia' : '',
        ]) ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
