<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $formData
 * @var bool $isEdit
 * @var array<string, string> $ambitos
 * @var array<string, array<string, string>> $tiposPorAmbito
 */

$niveles = [
    'info' => 'Info',
    'advertencia' => 'Advertencia',
    'critico' => 'Crítico',
];
$ambitoActual = (string)($formData['ambito'] ?? 'todos');
$tiposActuales = $tiposPorAmbito[$ambitoActual] ?? ['' => 'Todos los documentos'];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Nueva regla de alerta</h1>
    <p>Define cuándo generar avisos antes del vencimiento de un documento específico</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Datos de la regla</div></div>
  <div class="empresa-panel-body">
    <?= $this->Form->create(null) ?>
    <div class="empresa-form-grid">
      <?= $this->Form->control('ambito', [
          'label' => 'Ámbito *',
          'type' => 'select',
          'options' => $ambitos ?? [],
          'value' => $ambitoActual,
          'id' => 'regla-ambito',
          'required' => true,
      ]) ?>
      <?= $this->Form->control('tipo_documento', [
          'label' => 'Documento *',
          'type' => 'select',
          'options' => $tiposActuales,
          'value' => $formData['tipo_documento'] ?? '',
          'id' => 'regla-tipo-documento',
          'empty' => false,
      ]) ?>
      <?= $this->Form->control('dias_antes', [
          'label' => 'Días antes *',
          'type' => 'number',
          'min' => 0,
          'required' => true,
          'value' => $formData['dias_antes'] ?? 30,
          'help' => 'Avisar cuando falten estos días o menos para el vencimiento.',
      ]) ?>
      <?= $this->Form->control('nombre', [
          'label' => 'Nombre *',
          'required' => true,
          'value' => $formData['nombre'] ?? '',
          'placeholder' => 'Ej. Licencia federal — 30 días',
      ]) ?>
      <?= $this->Form->control('nivel_urgencia', [
          'label' => 'Nivel de urgencia',
          'type' => 'select',
          'options' => $niveles,
          'value' => $formData['nivel_urgencia'] ?? 'advertencia',
      ]) ?>
      <div class="field-span-2">
        <?= $this->Form->control('enviar_alerta', [
            'label' => 'Enviar alerta / notificación',
            'type' => 'checkbox',
            'checked' => !empty($formData['enviar_alerta']),
        ]) ?>
        <?= $this->Form->control('ocultar_despues', [
            'label' => 'Ocultar después del vencimiento',
            'type' => 'checkbox',
            'checked' => !empty($formData['ocultar_despues']),
        ]) ?>
        <?= $this->Form->control('mantener_visible', [
            'label' => 'Mantener visible en listados',
            'type' => 'checkbox',
            'checked' => !empty($formData['mantener_visible']),
        ]) ?>
        <?= $this->Form->control('activo', [
            'label' => 'Regla activa',
            'type' => 'checkbox',
            'checked' => !empty($formData['activo']),
        ]) ?>
      </div>
    </div>
    <p style="font-size:13px;color:var(--text3);margin:8px 0 16px">
      La regla solo aplica al documento elegido (o a todos si dejas “Todos…”).
      Las reglas globales (ámbito “Todos”) siguen cubriendo el resto.
    </p>
    <div class="empresa-form-actions">
      <button type="submit" class="btn btn-primary">Guardar regla</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<script>
(function () {
  var tiposPorAmbito = <?= json_encode($tiposPorAmbito ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var ambitoEl = document.getElementById('regla-ambito');
  var tipoEl = document.getElementById('regla-tipo-documento');
  if (!ambitoEl || !tipoEl) return;

  function refillTipos() {
    var ambito = ambitoEl.value || 'todos';
    var opciones = tiposPorAmbito[ambito] || { '': 'Todos los documentos' };
    var valorActual = tipoEl.value;
    tipoEl.innerHTML = '';
    Object.keys(opciones).forEach(function (key) {
      var opt = document.createElement('option');
      opt.value = key;
      opt.textContent = opciones[key];
      if (key === valorActual) opt.selected = true;
      tipoEl.appendChild(opt);
    });
    if (ambito === 'todos') {
      tipoEl.value = '';
    }
  }

  ambitoEl.addEventListener('change', function () {
    tipoEl.value = '';
    refillTipos();
  });
  refillTipos();
})();
</script>
