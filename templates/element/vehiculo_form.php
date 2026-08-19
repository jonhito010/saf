<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Vehiculo $vehiculo
 * @var array $empresas
 * @var array<string, string> $tipoCargaOptions
 * @var array<string, string> $modalidadOptions
 * @var array<string, string> $tipoServicioOptions
 * @var array<string, list<string>> $tipoServicioPorModalidad
 * @var bool $requiereCamposOperacion
 * @var bool $empresaFija
 * @var string $formTitulo
 */
use App\Model\Table\VehiculosTable;

$esEdicion = !$vehiculo->isNew();
$tipoCargaActual = (string)($vehiculo->tipo_carga ?? '');
$modalidadActual = (string)($vehiculo->modalidad ?? '');
$mostrarCamposOperacion = VehiculosTable::requiereCamposOperacion($tipoCargaActual);
$mostrarTipoServicio = $mostrarCamposOperacion && $modalidadActual === 'AUTOTRANSPORTE FEDERAL';
$catalogosOp = new \App\Service\CatalogosOperacionService();
$tiposConOperacionJs = [];
foreach (array_keys($catalogosOp->tipoCargaOptions()) as $codigoCarga) {
    if ($catalogosOp->requiereModalidad($codigoCarga)) {
        $tiposConOperacionJs[] = $codigoCarga;
    }
}
?>
<div class="veh-page">
  <div class="veh-toolbar">
    <a href="<?= $this->Url->build($esEdicion
        ? ['controller' => 'Vehiculos', 'action' => 'view', $vehiculo->id]
        : ['controller' => 'Vehiculos', 'action' => 'index']) ?>" class="btn btn-ghost btn-sm">← Regresar</a>
    <?php if ($esEdicion): ?>
      <?= $this->Form->postLink('Dar de baja', ['action' => 'delete', $vehiculo->id], [
          'confirm' => '¿Dar de baja (inactivar) este vehículo?',
          'class' => 'btn btn-ghost btn-sm',
      ]) ?>
    <?php endif; ?>
  </div>

  <div class="panel veh-form-panel">
    <div class="panel-header">
      <div class="panel-title"><?= h($formTitulo) ?></div>
      <span class="veh-form-hint">Datos de identificación y operación del vehículo</span>
    </div>
    <div class="veh-form-body">
      <?= $this->Form->create($vehiculo) ?>
      <fieldset class="form-section">
        <legend class="form-section-title">Empresa y placas</legend>
        <div class="veh-form-grid">
          <?php if (!empty($empresaFija) && $vehiculo->empresa_id): ?>
            <?= $this->Form->hidden('empresa_id', ['value' => $vehiculo->empresa_id]) ?>
            <div class="field-span-2 veh-field-readonly">
              <div class="data-label">Empresa</div>
              <div class="data-value"><?= h($empresas[$vehiculo->empresa_id] ?? '') ?></div>
            </div>
          <?php else: ?>
            <?= $this->Form->control('empresa_id', ['label' => 'Empresa', 'options' => $empresas, 'empty' => 'Selecciona…']) ?>
          <?php endif; ?>
          <?= $this->Form->control('placas', ['label' => 'Placas', 'class' => 'input-placas']) ?>
          <?= $this->Form->control('estatus', ['label' => 'Estatus del vehículo', 'options' => ['activo' => 'Activo', 'inactivo' => 'Inactivo']]) ?>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="form-section-title">Identificación del vehículo</legend>
        <div class="veh-form-grid">
          <?= $this->Form->control('marca', ['label' => 'Marca']) ?>
          <?= $this->Form->control('modelo', ['label' => 'Modelo']) ?>
          <?= $this->Form->control('anio', ['label' => 'Año']) ?>
          <?= $this->Form->control('color', ['label' => 'Color', 'required' => false]) ?>
          <?= $this->Form->control('numero_serie', ['label' => 'Número de serie (VIN)']) ?>
          <?= $this->Form->control('numero_motor', ['label' => 'Número de motor', 'required' => false]) ?>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="form-section-title">Operación y carga</legend>
        <div class="veh-form-grid">
          <?= $this->Form->control('tipo_carga', [
              'label' => 'Tipo de carga',
              'options' => $tipoCargaOptions,
              'empty' => 'Selecciona…',
              'id' => 'veh-tipo-carga',
          ]) ?>
          <div id="veh-campos-operacion" class="veh-campos-operacion field-span-2" style="<?= $mostrarCamposOperacion ? '' : 'display:none' ?>">
            <?= $this->element('vehiculo_modalidad_servicio') ?>
            <?= $this->Form->control('num_ejes', [
                'label' => 'Número de ejes',
                'required' => false,
                'id' => 'veh-num-ejes',
            ]) ?>
          </div>
          <?= $this->Form->control('peso_bruto_vehicular', ['label' => 'Peso bruto vehicular (kg)', 'required' => false]) ?>
        </div>
        <p id="veh-operacion-hint" class="veh-form-hint" style="<?= $mostrarCamposOperacion ? 'display:none' : '' ?>">
          Modalidad, tipo de servicio y número de ejes solo aplican a carga general o carga especializada (autotransporte federal).
        </p>
      </fieldset>

      <div class="veh-form-actions">
        <button class="btn btn-primary" type="submit"><?= $esEdicion ? 'Guardar cambios' : 'Registrar vehículo' ?></button>
        <a class="btn btn-ghost" href="<?= $this->Url->build($esEdicion
            ? ['controller' => 'Vehiculos', 'action' => 'view', $vehiculo->id]
            : ['controller' => 'Vehiculos', 'action' => 'index']) ?>">Cancelar</a>
      </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<script>
(function () {
  const tiposConOperacion = <?= json_encode($tiposConOperacionJs, JSON_UNESCAPED_UNICODE) ?>;
  const tipoServicioPorModalidad = <?= json_encode($tipoServicioPorModalidad ?? VehiculosTable::tipoServicioPorModalidad(), JSON_UNESCAPED_UNICODE) ?>;
  const todasTipoServicio = <?= json_encode(VehiculosTable::tipoServicioOptions(), JSON_UNESCAPED_UNICODE) ?>;

  const tipoCargaEl = document.getElementById('veh-tipo-carga');
  const bloqueOperacion = document.getElementById('veh-campos-operacion');
  const hintOperacion = document.getElementById('veh-operacion-hint');
  const modalidadEl = document.getElementById('veh-modalidad');
  const tipoServicioWrap = document.getElementById('veh-tipo-servicio-wrap');
  const tipoServicioEl = document.getElementById('veh-tipo-servicio');
  const numEjesEl = document.getElementById('veh-num-ejes');

  function limpiarSelect(select) {
    if (!select) return;
    select.value = '';
  }

  function actualizarTipoServicio(modalidad, conservarValor) {
    if (!tipoServicioEl) return;
    const permitidos = tipoServicioPorModalidad[modalidad] || [];
    const valorActual = conservarValor ? tipoServicioEl.value : '';
    tipoServicioEl.innerHTML = '';
    const vacio = document.createElement('option');
    vacio.value = '';
    vacio.textContent = '-- Tipo de servicio --';
    tipoServicioEl.appendChild(vacio);
    permitidos.forEach(function (key) {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = todasTipoServicio[key] || key;
      tipoServicioEl.appendChild(opt);
    });
    if (valorActual && permitidos.indexOf(valorActual) !== -1) {
      tipoServicioEl.value = valorActual;
    }
  }

  function sincronizarOperacion() {
    if (!tipoCargaEl || !bloqueOperacion) return;
    const tipoCarga = tipoCargaEl.value || '';
    const aplica = tiposConOperacion.indexOf(tipoCarga) !== -1;
    bloqueOperacion.style.display = aplica ? '' : 'none';
    if (hintOperacion) {
      hintOperacion.style.display = aplica ? 'none' : '';
    }
    if (!aplica) {
      if (modalidadEl) limpiarSelect(modalidadEl);
      if (tipoServicioEl) limpiarSelect(tipoServicioEl);
      if (numEjesEl) numEjesEl.value = '';
      if (tipoServicioWrap) tipoServicioWrap.style.display = 'none';
      return;
    }

    const modalidad = modalidadEl ? modalidadEl.value : '';
    const mostrarServicio = modalidad === 'AUTOTRANSPORTE FEDERAL';
    if (tipoServicioWrap) {
      tipoServicioWrap.style.display = mostrarServicio ? '' : 'none';
    }
    if (!mostrarServicio && tipoServicioEl) {
      limpiarSelect(tipoServicioEl);
    } else {
      actualizarTipoServicio(modalidad, true);
    }
  }

  if (tipoCargaEl) {
    tipoCargaEl.addEventListener('change', function () {
      sincronizarOperacion();
    });
  }
  if (modalidadEl) {
    modalidadEl.addEventListener('change', function () {
      sincronizarOperacion();
    });
  }

  sincronizarOperacion();
})();
</script>
