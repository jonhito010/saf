<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Operador $operador
 * @var string $empresaNombre
 * @var string $formTitulo
 * @var array<string, \App\Model\Entity\DocumentoOperador> $docsPorTipo
 * @var string $modo
 */

use App\Model\Entity\DocumentoOperador;
use App\Model\Table\DocumentosOperadorTable;
use App\Utility\DocumentoUtil;

$maxUploadLbl = DocumentoUtil::limiteUploadHumano();

$esAlta = ($modo ?? '') === 'alta' || $operador->isNew();
$indexUrl = $this->Url->build(['controller' => 'Operadores', 'action' => 'index']);

$tiposExp = DocumentosOperadorTable::tiposFormularioOperador();

$expTotal = 1 + count($tiposExp);
$expOk = !empty($operador->foto_url) ? 1 : 0;
$worstExp = 'vigente';
foreach ($tiposExp as $tipo) {
    if (isset($docsPorTipo[$tipo])) {
        $expOk++;
        $est = (string)($docsPorTipo[$tipo]->estatus ?? '');
        if ($est === 'vencido') {
            $worstExp = 'vencido';
        } elseif ($est === 'por_vencer' && $worstExp !== 'vencido') {
            $worstExp = 'por_vencer';
        }
    } elseif ($worstExp === 'vigente') {
        $worstExp = 'faltante';
    }
}
$expPct = $expTotal > 0 ? (int)round(100 * $expOk / $expTotal) : 0;
$fillClass = $expPct < 60 ? 'fill-red' : ($expPct < 100 ? 'fill-amber' : 'fill-teal');

$expBadge = match ($worstExp) {
    'vencido' => ['badge-red', 'Documentación vencida'],
    'por_vencer' => ['badge-amber', 'Por vencer'],
    'faltante' => ['badge-gray', 'Expediente incompleto'],
    default => ['badge-green', 'Expediente al día'],
};

$docLic = $docsPorTipo['licencia'] ?? null;
$licenciaFecha = ($docLic instanceof DocumentoOperador && $docLic->fecha_vencimiento)
    ? $docLic->fecha_vencimiento->format('d/m/Y')
    : null;

$displayName = trim($operador->nombre_completo) !== ''
    ? $operador->nombre_completo
    : ($esAlta ? 'Nuevo operador' : 'Operador');
$kickerAccion = $esAlta ? 'Registrar operador' : 'Editar operador';
?>

<div class="oper-page oper-form-page">
  <header class="oper-form-hero">
    <div class="oper-form-hero-top">
      <a href="<?= $indexUrl ?>" class="btn btn-ghost btn-sm">← Operadores</a>
      <?php if (!$esAlta): ?>
        <?= $this->Form->postLink('Dar de baja', ['action' => 'delete', $operador->id], [
            'confirm' => '¿Dar de baja (inactivar) este operador?',
            'class' => 'btn btn-ghost btn-sm oper-form-baja',
        ]) ?>
      <?php endif; ?>
    </div>

    <div class="oper-form-profile">
      <div class="oper-form-profile-main">
        <?php if (!empty($operador->foto_url)): ?>
          <div class="oper-form-profile-avatar oper-form-profile-avatar--foto">
            <img src="<?= h($this->Url->build($operador->foto_url)) ?>" alt="">
          </div>
        <?php elseif (trim($operador->iniciales) !== ''): ?>
          <div class="oper-form-profile-avatar"><?= h($operador->iniciales) ?></div>
        <?php else: ?>
          <div class="oper-form-profile-avatar oper-form-profile-avatar--empty" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="10" r="4"/><path d="M5 20c0-4 3-7 7-7s7 3 7 7"/></svg>
          </div>
        <?php endif; ?>
        <div class="oper-form-profile-text">
          <p class="oper-form-profile-kicker"><?= h($kickerAccion) ?> · <?= h($empresaNombre) ?></p>
          <h1 class="oper-form-title oper-form-title--profile"><?= h($displayName) ?></h1>
          <p class="oper-form-profile-meta">
            <?php if (!empty($operador->curp)): ?><span><?= h($operador->curp) ?></span><?php endif; ?>
            <?php if (!empty($operador->rfc)): ?><span><?= h($operador->rfc) ?></span><?php endif; ?>
            <?php if (!empty($operador->num_licencia)): ?><span>Lic. <?= h($operador->num_licencia) ?></span><?php endif; ?>
            <?php if (empty($operador->curp) && empty($operador->rfc) && empty($operador->num_licencia)): ?>
              <span class="oper-form-profile-meta--muted"><?= $esAlta ? 'Completa los datos al guardar' : 'Sin identificadores' ?></span>
            <?php endif; ?>
          </p>
        </div>
      </div>
      <div class="oper-form-profile-side">
        <?php if ($esAlta): ?>
          <span class="oper-form-badge">Nuevo</span>
        <?php else: ?>
          <span class="badge <?= match ($operador->estatus ?? 'activo') {
              'activo' => 'badge-green',
              'suspendido' => 'badge-amber',
              'baja' => 'badge-red',
              default => 'badge-gray',
          } ?>">
            <?= h(match ($operador->estatus ?? 'activo') {
                'activo' => 'Activo',
                'inactivo' => 'Inactivo',
                'suspendido' => 'Suspendido',
                'baja' => 'Baja',
                default => ucfirst((string)($operador->estatus ?? 'activo')),
            }) ?>
          </span>
        <?php endif; ?>
        <span class="badge <?= h($expBadge[0]) ?>"><?= h($expBadge[1]) ?></span>
      </div>
    </div>

    <div class="oper-form-profile-licencia">
      <span class="oper-form-profile-licencia-label">Vencimiento licencia</span>
      <?php if ($licenciaFecha): ?>
        <strong><?= h($licenciaFecha) ?></strong>
        <?php if ($docLic instanceof DocumentoOperador): ?>
          <span class="oper-form-profile-licencia-est oper-form-profile-licencia-est--<?= h($docLic->estatus ?? 'vigente') ?>">
            <?= h(match ($docLic->estatus ?? '') {
                'vencido' => 'Vencida',
                'por_vencer' => 'Por vencer',
                default => 'Vigente',
            }) ?>
          </span>
        <?php endif; ?>
      <?php else: ?>
        <em class="oper-form-profile-licencia-empty"><?= $esAlta ? 'Captura archivo y fecha en expediente' : 'Sin fecha registrada' ?></em>
      <?php endif; ?>
    </div>

    <nav class="oper-form-nav" aria-label="Secciones del formulario">
      <a href="#oper-datos-personales">Datos personales</a>
      <a href="#oper-expediente">Expediente</a>
      <a href="#oper-domicilio">Domicilio</a>
    </nav>
  </header>

  <div class="oper-form-layout">
    <div class="oper-form-main">
      <?= $this->Form->create($operador, ['type' => 'file', 'class' => 'oper-form']) ?>

      <section class="oper-form-card" id="oper-datos-personales">
        <div class="oper-form-card-head">
          <span class="oper-form-card-num">1</span>
          <div>
            <h2 class="oper-form-card-title">Datos personales</h2>
            <p class="oper-form-card-desc">Identificación del operador y datos de contacto.</p>
          </div>
        </div>
        <div class="oper-form-card-body">
          <div class="veh-form-grid">
            <?php if ($esAlta): ?>
              <?= $this->Form->hidden('estatus', ['value' => 'activo']) ?>
            <?php endif; ?>
            <div class="field-span-2 oper-form-empresa">
              <span class="oper-form-empresa-label">Empresa</span>
              <?php
                $empresasOpts = $empresas ?? [];
                $empresaFija = !empty($empresaFija) || count($empresasOpts) <= 1;
              ?>
              <?php if ($esAlta && !$empresaFija && $empresasOpts !== []): ?>
                <?= $this->Form->control('empresa_id', [
                    'label' => ['text' => 'Empresa <span class="req" aria-hidden="true">*</span>', 'escape' => false],
                    'type' => 'select',
                    'options' => $empresasOpts,
                    'value' => $operador->empresa_id,
                    'required' => true,
                    'class' => 'oper-form-empresa-select',
                ]) ?>
              <?php else: ?>
                <?= $this->Form->hidden('empresa_id', ['value' => $operador->empresa_id]) ?>
                <span class="oper-form-empresa-name"><?= h($empresaNombre) ?> <span class="req" aria-hidden="true">*</span></span>
              <?php endif; ?>
            </div>
            <?= $this->Form->control('nombre', ['label' => ['text' => 'Nombre(s) <span class="req" aria-hidden="true">*</span>', 'escape' => false], 'required' => true, 'autocomplete' => 'given-name', 'class' => 'input-upper', 'maxlength' => 80, 'oninput' => "this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚÑÜáéíóúñü\\s]/g,'').toUpperCase()"]) ?>
            <?= $this->Form->control('apellido_paterno', ['label' => ['text' => 'Apellido paterno <span class="req" aria-hidden="true">*</span>', 'escape' => false], 'required' => true, 'autocomplete' => 'family-name', 'class' => 'input-upper', 'maxlength' => 80, 'oninput' => "this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚÑÜáéíóúñü\\s]/g,'').toUpperCase()"]) ?>
            <?= $this->Form->control('apellido_materno', ['label' => 'Apellido materno', 'required' => false, 'autocomplete' => 'additional-name', 'class' => 'input-upper', 'maxlength' => 80, 'oninput' => "this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚÑÜáéíóúñü\\s]/g,'').toUpperCase()"]) ?>
            <?= $this->Form->control('curp', [
                'label' => 'CURP',
                'required' => false,
                'class' => 'input-mono',
                'maxlength' => 18,
                'minlength' => 18,
                'pattern' => '[A-Z][AEIOUX][A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[HM](AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]',
                'title' => 'CURP de 18 caracteres con formato válido (ej. GARC850101HDFRRN09)',
                'placeholder' => 'ABCD000101HDFRRN09',
                'style' => 'text-transform:uppercase',
                'oninput' => "this.value=this.value.replace(/\\s/g,'').toUpperCase()",
            ]) ?>
            <?= $this->Form->control('rfc', [
                'label' => 'RFC (Carta Porte)',
                'required' => false,
                'maxlength' => 13,
                'class' => 'input-mono',
                'pattern' => '[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}',
                'title' => '3-4 letras + 6 dígitos + 3 alfanuméricos. Ej: GARC850101XXX',
                'placeholder' => 'GARC850101XXX',
                'style' => 'text-transform:uppercase',
                'oninput' => "this.value=this.value.replace(/\\s/g,'').toUpperCase()",
            ]) ?>
            <?= $this->Form->control('num_licencia', [
                'label' => ['text' => 'Número de licencia federal <span class="req" aria-hidden="true">*</span>', 'escape' => false],
                'required' => true,
                'class' => 'input-mono',
                'maxlength' => 30,
                'minlength' => 6,
                'pattern' => '[A-Z0-9]{6,30}',
                'title' => 'Solo letras y números, 6 a 30 caracteres',
                'style' => 'text-transform:uppercase',
                'oninput' => "this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()",
            ]) ?>
            <?php
              $telDigits = preg_replace('/\D/', '', (string)($operador->telefono ?? ''));
              $telMostrar = strlen($telDigits) === 10
                  ? substr($telDigits, 0, 2) . ' ' . substr($telDigits, 2, 4) . ' ' . substr($telDigits, 6, 4)
                  : $telDigits;
            ?>
            <?= $this->Form->control('telefono', [
                'label' => 'Teléfono',
                'required' => false,
                'type' => 'tel',
                'class' => 'input-mono input-telefono',
                'value' => $telMostrar,
                'maxlength' => 12,
                'inputmode' => 'numeric',
                'autocomplete' => 'tel-national',
                'pattern' => '[0-9]{2} [0-9]{4} [0-9]{4}',
                'placeholder' => '81 1234 5678',
                'title' => '10 dígitos. Ej: 81 1234 5678',
                'oninput' => "var d=this.value.replace(/\\D/g,'').slice(0,10);this.value=d.length>6?d.slice(0,2)+' '+d.slice(2,6)+' '+d.slice(6):(d.length>2?d.slice(0,2)+' '+d.slice(2):d)",
            ]) ?>
            <?= $this->Form->control('email', ['label' => 'Correo electrónico', 'required' => false, 'type' => 'email']) ?>
            <?= $this->Form->control('fecha_ingreso', ['label' => 'Fecha de ingreso', 'empty' => true, 'required' => false, 'type' => 'date']) ?>
            <?php if (!$esAlta): ?>
              <?= $this->Form->control('estatus', [
                  'label' => ['text' => 'Estatus <span class="req" aria-hidden="true">*</span>', 'escape' => false],
                  'required' => true,
                  'options' => [
                      'activo' => 'Activo',
                      'inactivo' => 'Inactivo',
                      'suspendido' => 'Suspendido',
                      'baja' => 'Baja',
                  ],
              ]) ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <?= $this->element('operador_archivos_form', [
          'docsPorTipo' => $docsPorTipo,
          'esAlta' => $esAlta,
      ]) ?>

      <details class="oper-form-card oper-form-collapse" id="oper-domicilio">
        <summary class="oper-form-card-head oper-form-collapse-summary">
          <span class="oper-form-card-num">3</span>
          <div class="oper-form-collapse-text">
            <h2 class="oper-form-card-title">Domicilio (Carta Porte 3.1)</h2>
            <p class="oper-form-card-desc">Domicilio fiscal del operador para figura de transporte.</p>
          </div>
          <span class="oper-form-collapse-chevron" aria-hidden="true"></span>
        </summary>
        <div class="oper-form-card-body">
          <div class="veh-form-grid">
            <?= $this->Form->control('domicilio_codigo_postal', ['label' => 'Código postal', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_calle', ['label' => 'Calle', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_numero_exterior', ['label' => 'Número exterior', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_numero_interior', ['label' => 'Número interior', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_colonia', ['label' => 'Colonia', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_localidad', ['label' => 'Localidad', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_referencia', ['label' => 'Referencia', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_municipio', ['label' => 'Municipio', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_estado', ['label' => 'Estado (clave SAT)', 'required' => false]) ?>
            <?= $this->Form->control('domicilio_pais', ['label' => 'País (clave SAT)', 'required' => false, 'value' => $operador->domicilio_pais ?? 'MEX']) ?>
            <?= $this->Form->control('num_reg_id_trib_figura', ['label' => 'NumRegIdTrib (extranjero)', 'required' => false]) ?>
            <?= $this->Form->control('residencia_fiscal_figura', ['label' => 'Residencia fiscal (extranjero)', 'required' => false]) ?>
          </div>
        </div>
      </details>

      <div class="oper-form-actions">
        <button class="btn btn-primary" type="submit">
          <?= $esAlta ? 'Registrar operador' : 'Guardar cambios' ?>
        </button>
        <a class="btn btn-ghost" href="<?= $indexUrl ?>">Cancelar</a>
      </div>
      <?= $this->Form->end() ?>
    </div>

    <aside class="oper-form-aside">
      <div class="oper-form-aside-card oper-form-aside-card--status">
        <h3 class="oper-form-aside-title">Estado del expediente</h3>
        <div class="oper-form-aside-pct">
          <span>Cumplimiento</span>
          <strong><?= $expPct ?>%</strong>
        </div>
        <div class="cumpl-bar"><div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $expPct ?>%"></div></div>
        <ul class="oper-form-doc-status">
          <li class="<?= !empty($operador->foto_url) ? 'is-ok' : 'is-pending' ?>">
            <span class="oper-form-doc-dot"></span>
            <span class="oper-form-doc-name">Foto</span>
            <span class="oper-form-doc-val"><?= !empty($operador->foto_url) ? 'Cargada' : 'Pendiente' ?></span>
          </li>
          <?php foreach ($tiposExp as $tipo):
              $doc = $docsPorTipo[$tipo] ?? null;
              $liClass = 'is-pending';
              $valTxt = 'Pendiente';
              if ($doc instanceof DocumentoOperador) {
                  $liClass = match ($doc->estatus ?? '') {
                      'vencido' => 'is-bad',
                      'por_vencer' => 'is-warn',
                      default => 'is-ok',
                  };
                  $valTxt = match ($doc->estatus ?? '') {
                      'vencido' => 'Vencido',
                      'por_vencer' => 'Por vencer',
                      default => 'Vigente',
                  };
                  if ($doc->fecha_vencimiento) {
                      $valTxt .= ' · ' . $doc->fecha_vencimiento->format('d/m/y');
                  }
              }
          ?>
          <li class="<?= $liClass ?>">
            <span class="oper-form-doc-dot"></span>
            <span class="oper-form-doc-name"><?= h(DocumentosOperadorTable::tipoLabel($tipo)) ?></span>
            <span class="oper-form-doc-val"><?= h($valTxt) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="#oper-expediente" class="oper-form-aside-link">Ir a expediente ↓</a>
        <?php if ($esAlta): ?>
          <p class="oper-form-aside-note">Al registrar podrás seguir en esta pantalla para completar archivos pendientes.</p>
        <?php endif; ?>
      </div>
      <div class="oper-form-aside-card oper-form-aside-card--muted">
        <h3 class="oper-form-aside-title">Formatos</h3>
        <ul class="oper-form-tips">
          <li>Foto: JPG o PNG (máx. <?= h($maxUploadLbl) ?>)</li>
          <li>Obligatorios: licencia federal, INE y comprobante de domicilio</li>
          <li>Licencia: indica siempre la fecha de vencimiento</li>
        </ul>
      </div>
    </aside>
  </div>
</div>
<script>
(function () {
  var form = document.querySelector('.oper-form-page form.oper-form');
  if (!form) return;
  form.addEventListener('submit', function (ev) {
    var faltantes = [];
    var nombre = form.querySelector('[name="nombre"]');
    var apPat = form.querySelector('[name="apellido_paterno"]');
    var licencia = form.querySelector('[name="num_licencia"]');
    var empresa = form.querySelector('[name="empresa_id"]');
    var estatus = form.querySelector('[name="estatus"]');
    var fechaLic = form.querySelector('[name="fecha_vencimiento_licencia"]');
    if (nombre && !nombre.value.trim()) faltantes.push('Nombre(s)');
    if (apPat && !apPat.value.trim()) faltantes.push('Apellido paterno');
    if (licencia && !licencia.value.trim()) faltantes.push('Número de licencia federal');
    if (empresa && empresa.tagName === 'SELECT' && !empresa.value) faltantes.push('Empresa');
    if (estatus && estatus.tagName === 'SELECT' && !estatus.value) faltantes.push('Estatus');
    if (fechaLic && !fechaLic.value) faltantes.push('Fecha de vencimiento de la licencia');
    if (faltantes.length) {
      ev.preventDefault();
      alert('Completa los campos obligatorios:\n\n• ' + faltantes.join('\n• '));
    }
  });
})();
</script>
