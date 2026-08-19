<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ubicacion $ubicacion
 * @var array $tipoUbicacionOptions
 */
$__paises = [
    'MEX'=>'México','USA'=>'Estados Unidos de América','CAN'=>'Canadá',
    'GTM'=>'Guatemala','BLZ'=>'Belice','HND'=>'Honduras','SLV'=>'El Salvador',
    'NIC'=>'Nicaragua','CRI'=>'Costa Rica','PAN'=>'Panamá',
    'CUB'=>'Cuba','DOM'=>'República Dominicana','HTI'=>'Haití',
    'JAM'=>'Jamaica','TTO'=>'Trinidad y Tobago','BRB'=>'Barbados',
    'ATG'=>'Antigua y Barbuda','DMA'=>'Dominica','GRD'=>'Granada',
    'KNA'=>'San Cristóbal y Nieves','LCA'=>'Santa Lucía',
    'VCT'=>'San Vicente y las Granadinas','BHS'=>'Bahamas',
    'PRI'=>'Puerto Rico','CYM'=>'Islas Caimán','ABW'=>'Aruba','CUW'=>'Curaçao',
    'COL'=>'Colombia','VEN'=>'Venezuela, República Bolivariana de','ECU'=>'Ecuador',
    'PER'=>'Perú','BOL'=>'Bolivia, Estado Plurinacional de','PRY'=>'Paraguay',
    'CHL'=>'Chile','ARG'=>'Argentina','URY'=>'Uruguay','GUY'=>'Guyana',
    'SUR'=>'Surinam','BRA'=>'Brasil',
    'ESP'=>'España','PRT'=>'Portugal','FRA'=>'Francia','GBR'=>'Reino Unido',
    'IRL'=>'Irlanda','DEU'=>'Alemania','AUT'=>'Austria','CHE'=>'Suiza',
    'ITA'=>'Italia','BEL'=>'Bélgica','NLD'=>'Países Bajos','LUX'=>'Luxemburgo',
    'DNK'=>'Dinamarca','SWE'=>'Suecia','NOR'=>'Noruega','FIN'=>'Finlandia',
    'ISL'=>'Islandia','POL'=>'Polonia','CZE'=>'República Checa',
    'SVK'=>'Eslovaquia','SVN'=>'Eslovenia','HRV'=>'Croacia',
    'HUN'=>'Hungría','ROU'=>'Rumanía','BGR'=>'Bulgaria','GRC'=>'Grecia',
    'CYP'=>'Chipre','MLT'=>'Malta','EST'=>'Estonia','LVA'=>'Letonia',
    'LTU'=>'Lituania','MKD'=>'Macedonia del Norte','ALB'=>'Albania',
    'BIH'=>'Bosnia y Herzegovina','SRB'=>'Serbia','MNE'=>'Montenegro',
    'RUS'=>'Rusia, Federación de','UKR'=>'Ucrania','BLR'=>'Bielorrusia',
    'TUR'=>'Turquía','ISR'=>'Israel','JOR'=>'Jordania','LBN'=>'Líbano',
    'IRQ'=>'Iraq','IRN'=>'Irán, República Islámica del',
    'SAU'=>'Arabia Saudita','ARE'=>'Emiratos Árabes Unidos','KWT'=>'Kuwait',
    'QAT'=>'Catar','BHR'=>'Baréin','OMN'=>'Omán',
    'IND'=>'India','PAK'=>'Pakistán','BGD'=>'Bangladés','LKA'=>'Sri Lanka',
    'CHN'=>'China','JPN'=>'Japón','KOR'=>'Corea, República de',
    'TWN'=>'Taiwán, Provincia de China','HKG'=>'Hong Kong',
    'THA'=>'Tailandia','VNM'=>'Viet Nam','MYS'=>'Malasia','SGP'=>'Singapur',
    'IDN'=>'Indonesia','PHL'=>'Filipinas',
    'AUS'=>'Australia','NZL'=>'Nueva Zelanda',
    'EGY'=>'Egipto','MAR'=>'Marruecos','DZA'=>'Argelia','TUN'=>'Túnez',
    'NGA'=>'Nigeria','ZAF'=>'Sudáfrica','KEN'=>'Kenya','ETH'=>'Etiopía',
];
?>

<div style="margin-bottom:12px;display:flex;gap:10px;align-items:center;justify-content:space-between">
  <a href="<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
  <span class="tag">ID <?= h($ubicacion->id) ?></span>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Editar ubicación</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($ubicacion) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?= $this->Form->control('tipo_ubicacion', ['label' => 'Tipo de ubicación', 'options' => $tipoUbicacionOptions]) ?>
      <?= $this->Form->control('id_ubicacion', [
          'label' => 'Clave de ubicación',
          'maxlength' => 10,
          'readonly' => true,
      ]) ?>

      <?= $this->Form->control('rfc', ['label' => 'RFC', 'maxlength' => 13, 'oninput' => 'this.value=this.value.toUpperCase()']) ?>
      <?= $this->Form->control('nombre', ['label' => 'Nombre', 'maxlength' => 255]) ?>

      <?= $this->Form->control('calle', ['label' => 'Calle', 'maxlength' => 255]) ?>
      <?= $this->Form->control('numero_exterior', ['label' => 'Número exterior', 'maxlength' => 50]) ?>
      <?= $this->Form->control('numero_interior', ['label' => 'Número interior', 'required' => false, 'maxlength' => 50]) ?>
      <div></div>

      <?= $this->Form->control('codigo_postal', ['label' => 'Código postal', 'maxlength' => 5, 'placeholder' => '00000', 'autocomplete' => 'off']) ?>
      <?= $this->Form->control('colonia', ['label' => 'Colonia (clave)', 'maxlength' => 10, 'placeholder' => 'Primero captura el C.P.', 'autocomplete' => 'off']) ?>
      <?= $this->Form->control('estado', ['label' => 'Estado (clave)', 'maxlength' => 3, 'autocomplete' => 'off']) ?>
      <?= $this->Form->control('municipio', ['label' => 'Municipio (clave)', 'maxlength' => 5, 'autocomplete' => 'off']) ?>
      <?= $this->Form->control('localidad', ['label' => 'Localidad (clave)', 'required' => false, 'maxlength' => 10, 'autocomplete' => 'off']) ?>

      <div>
        <label class="form-label" style="display:block;margin-bottom:4px;font-size:13px">País</label>
        <select name="pais" id="pais" class="form-control">
          <option value="">— Sin especificar —</option>
          <?php foreach ($__paises as $__pc => $__pn): ?>
          <option value="<?= $__pc ?>" <?= ($ubicacion->pais ?? '') === $__pc ? 'selected' : '' ?>><?= $__pc . ' - ' . $__pn ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="grid-column:1/-1">
        <?= $this->Form->control('referencia', ['label' => 'Referencia', 'required' => false, 'maxlength' => 255]) ?>
      </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<script>
(function () {
  function el(id) { return document.getElementById(id); }
  var tipo = el('tipo-ubicacion') || el('tipo_ubicacion');
  var clave = el('id-ubicacion') || el('id_ubicacion');
  if (!tipo || !clave) return;

  function prefixFromTipo(v) {
    if (v === 'Origen') return 'OR';
    if (v === 'Destino') return 'DE';
    return '';
  }
  function pad6(n) {
    var s = String(n);
    while (s.length < 6) s = '0' + s;
    return s;
  }

  function syncPrefix() {
    var pref = prefixFromTipo(tipo.value);
    if (!pref) return;
    var v = (clave.value || '').trim();
    var m = v.match(/^(?:OR|DE)\s*(\d{1,6})$/i);
    if (m) clave.value = pref + pad6(m[1]);
  }

  tipo.addEventListener('change', syncPrefix);
})();
</script>

<script>
(function () {
  const cp = document.getElementById('codigo-postal');
  const colonia = document.getElementById('colonia');
  const estado = document.getElementById('estado');
  const municipio = document.getElementById('municipio');
  const localidad = document.getElementById('localidad');

  function attachCfdiAutocomplete(opts) {
    const input = opts.input;
    if (!input) return;

    const wrap = input.closest('.input') || input.parentElement;
    if (wrap) wrap.classList.add('cfdi-ac-wrap');

    const resultsBox = document.createElement('div');
    resultsBox.className = 'cfdi-ac-dropdown hidden';
    resultsBox.setAttribute('role', 'listbox');
    resultsBox.setAttribute('aria-label', opts.ariaLabel || 'Resultados');
    (wrap || input.parentElement).appendChild(resultsBox);

    let controller;
    let timer;

    function clearResults() {
      resultsBox.innerHTML = '';
      resultsBox.classList.add('hidden');
    }

    function renderResults(items) {
      if (!items || !items.length) {
        clearResults();
        return;
      }
      resultsBox.innerHTML = '';
      const head = document.createElement('div');
      head.className = 'cfdi-ac-dropdown__head';
      head.textContent = items.length === 1 ? '1 resultado' : items.length + ' resultados';
      resultsBox.appendChild(head);

      items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cfdi-ac-item';
        btn.setAttribute('role', 'option');

        const title = document.createElement('div');
        title.className = 'cfdi-ac-item__title';
        title.textContent = item.label || item.name || item.value || '—';
        btn.appendChild(title);

        const meta = document.createElement('div');
        meta.className = 'cfdi-ac-item__meta';

        if (item.key || item.value) {
          const b = document.createElement('span');
          b.className = 'cfdi-ac-badge';
          b.textContent = String(item.key || item.value).trim();
          meta.appendChild(b);
        }

        if (item.name && (item.key || item.value)) {
          const m = document.createElement('span');
          m.className = 'cfdi-ac-meta';
          m.textContent = String(item.name).trim();
          meta.appendChild(m);
        }

        if (meta.childNodes.length) btn.appendChild(meta);

        btn.addEventListener('click', () => {
          input.value = (item.value !== undefined && item.value !== null) ? String(item.value) : '';
          clearResults();
          if (typeof opts.onPick === 'function') opts.onPick(item);
        });

        resultsBox.appendChild(btn);
      });
      resultsBox.classList.remove('hidden');
    }

    input.addEventListener('input', function () {
      const q = input.value.trim();
      if (timer) clearTimeout(timer);
      if (controller) controller.abort();
      if (q.length < (opts.minChars || 1)) {
        clearResults();
        return;
      }
      timer = setTimeout(async () => {
        try {
          controller = new AbortController();
          const url = opts.url(q);
          const res = await fetch(url, { signal: controller.signal });
          const json = await res.json();
          renderResults(Array.isArray(json) ? json : (json.results || []));
        } catch (e) {}
      }, opts.delayMs || 250);
    });

    document.addEventListener('click', function (e) {
      if (!resultsBox.contains(e.target) && e.target !== input) clearResults();
    });
  }

  const urlCp = '<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'autocompleteCodigoPostal']) ?>';
  const urlEstados = '<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'autocompleteEstados']) ?>';
  const urlMunicipios = '<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'autocompleteMunicipios']) ?>';
  const urlLocalidades = '<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'autocompleteLocalidades']) ?>';
  const urlColonias = '<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'autocompleteColonias']) ?>';

  function withParams(baseUrl, params) {
    const u = new URL(baseUrl, window.location.origin);
    Object.keys(params || {}).forEach(k => {
      const v = params[k];
      if (v !== undefined && v !== null && String(v).trim() !== '') u.searchParams.set(k, v);
    });
    return u.toString();
  }

  attachCfdiAutocomplete({
    input: cp,
    ariaLabel: 'Resultados de código postal',
    minChars: 2,
    url: (q) => withParams(urlCp, { q }),
    onPick: () => {
      if (colonia) colonia.value = '';
      if (colonia) colonia.dispatchEvent(new Event('input', { bubbles: true }));
    },
  });

  attachCfdiAutocomplete({
    input: estado,
    ariaLabel: 'Resultados de estados',
    minChars: 1,
    url: (q) => withParams(urlEstados, { q }),
  });

  attachCfdiAutocomplete({
    input: municipio,
    ariaLabel: 'Resultados de municipios',
    minChars: 1,
    url: (q) => withParams(urlMunicipios, { estado: (estado ? estado.value : ''), q }),
  });

  attachCfdiAutocomplete({
    input: localidad,
    ariaLabel: 'Resultados de localidades',
    minChars: 1,
    url: (q) => withParams(urlLocalidades, { estado: (estado ? estado.value : ''), q }),
  });

  attachCfdiAutocomplete({
    input: colonia,
    ariaLabel: 'Resultados de colonias',
    minChars: 1,
    url: (q) => withParams(urlColonias, { cp: (cp ? cp.value : ''), q }),
  });
})();
</script>

