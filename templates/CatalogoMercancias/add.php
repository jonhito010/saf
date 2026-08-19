<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CatalogoMercancia $mercancia
 */
?>
<style>
.cm-ac-wrap { position: relative; }
.cm-ac-drop {
  display: none;
  position: absolute;
  left: 0; right: 0;
  top: calc(100% + 2px);
  z-index: 9999;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-shadow: 0 6px 18px rgba(0,0,0,.15);
  max-height: 230px;
  overflow-y: auto;
}
.cm-ac-item {
  padding: 7px 11px;
  cursor: pointer;
  font-size: 12px;
  border-bottom: 1px solid #f0f0f0;
  line-height: 1.4;
}
.cm-ac-item:last-child { border-bottom: 0; }
.cm-ac-item:hover { background: #e6f7f6; }
.cm-ac-key {
  font-family: 'DM Mono', monospace;
  font-weight: 700;
  color: #2BBFB3;
  margin-right: 6px;
}
.cm-ac-nom { color: #444; }
</style>
<div style="margin-bottom:12px">
  <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-ghost" style="font-size:12px">← Regresar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Nueva mercancía en catálogo</div></div>
  <div style="padding:16px 18px">
    <?= $this->Form->create($mercancia) ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

      <div style="grid-column:1/-1">
        <?= $this->Form->control('descripcion', ['label' => 'Descripción *', 'maxlength' => 255, 'placeholder' => 'Ej. Cemento gris en sacos']) ?>
      </div>

      <div>
        <label style="display:block;font-size:13px;margin-bottom:4px">Clave SAT (BienesTransp) *</label>
        <input type="text" name="bienes_transp" id="bienes_transp"
               class="form-control" maxlength="8"
               value="<?= h($mercancia->bienes_transp ?? '') ?>"
               placeholder="Ej. 24141503" autocomplete="off">
        <small style="color:var(--text3);font-size:11px">Catálogo SAT c_ClaveProdServCP</small>
      </div>

      <div>
        <label style="display:block;font-size:13px;margin-bottom:4px">Clave de unidad (ClaveUnidad) *</label>
        <input type="text" name="clave_unidad" id="clave_unidad"
               class="form-control" maxlength="3"
               value="<?= h($mercancia->clave_unidad ?? '') ?>"
               placeholder="Ej. KGM, H87, E48" autocomplete="off">
        <small style="color:var(--text3);font-size:11px">Catálogo SAT c_ClaveUnidad</small>
      </div>

      <div>
        <label style="display:block;font-size:13px;margin-bottom:4px">Peso unitario de referencia (kg)</label>
        <input type="number" name="peso_en_kg" id="peso_en_kg"
               class="form-control" min="0" step="0.001"
               value="<?= h($mercancia->peso_en_kg ?? '') ?>"
               placeholder="Opcional — ej. 50.000">
      </div>

      <div style="display:flex;align-items:center;gap:10px;padding-top:24px">
        <input type="hidden" name="material_peligroso" value="0">
        <input type="checkbox" name="material_peligroso" id="material_peligroso" value="1"
               <?= $mercancia->material_peligroso ? 'checked' : '' ?>>
        <label for="material_peligroso" style="font-size:13px;cursor:pointer;margin:0">
          Material / residuo peligroso
        </label>
      </div>

      <div id="div_peligroso" style="display:<?= $mercancia->material_peligroso ? 'grid' : 'none' ?>;grid-column:1/-1;grid-template-columns:1fr 1fr;gap:14px">
        <div>
          <label style="display:block;font-size:13px;margin-bottom:4px">Clave material peligroso</label>
          <input type="text" name="cve_material_peligroso" id="cve_material_peligroso"
                 class="form-control" maxlength="4"
                 value="<?= h($mercancia->cve_material_peligroso ?? '') ?>"
                 placeholder="Ej. 1203">
          <small style="color:var(--text3);font-size:11px">Catálogo SAT c_MaterialPeligroso</small>
        </div>
        <div>
          <label style="display:block;font-size:13px;margin-bottom:4px">Embalaje</label>
          <input type="text" name="embalaje" id="embalaje"
                 class="form-control" maxlength="4"
                 value="<?= h($mercancia->embalaje ?? '') ?>"
                 placeholder="Ej. 4G">
          <small style="color:var(--text3);font-size:11px">Catálogo SAT c_TipoEmbalaje</small>
        </div>
      </div>

    </div>

    <div style="margin-top:18px;display:flex;gap:10px">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<script>
(function () {
  var chk = document.getElementById('material_peligroso');
  var div = document.getElementById('div_peligroso');
  if (chk && div) {
    chk.addEventListener('change', function () {
      div.style.display = chk.checked ? 'grid' : 'none';
    });
  }
})();
</script>

<script>
(function () {
  var urlBienes  = '<?= $this->Url->build(['controller' => 'CatalogoMercancias', 'action' => 'autocompleteBienes']) ?>';
  var urlUnidad  = '<?= $this->Url->build(['controller' => 'CatalogoMercancias', 'action' => 'autocompleteUnidad']) ?>';

  function attachAc(inputId, url, onPick) {
    var inp = document.getElementById(inputId);
    if (!inp) return;

    inp.parentNode.classList.add('cm-ac-wrap');
    var drop = document.createElement('div');
    drop.className = 'cm-ac-drop';
    inp.parentNode.appendChild(drop);

    var timer = null;

    function render(items) {
      drop.innerHTML = '';
      if (!items.length) { drop.style.display = 'none'; return; }
      items.forEach(function (it) {
        var row = document.createElement('div');
        row.className = 'cm-ac-item';
        var key = document.createElement('span');
        key.className = 'cm-ac-key';
        key.textContent = it.id;
        var nom = document.createElement('span');
        nom.className = 'cm-ac-nom';
        nom.textContent = it.nombre || it.descripcion || '';
        row.appendChild(key);
        row.appendChild(nom);
        row.addEventListener('mousedown', function (e) {
          e.preventDefault();
          inp.value = it.id;
          drop.style.display = 'none';
          if (onPick) onPick(it);
        });
        drop.appendChild(row);
      });
      drop.style.display = 'block';
    }

    inp.addEventListener('input', function () {
      clearTimeout(timer);
      var q = inp.value.trim();
      if (q.length < 1) { drop.style.display = 'none'; return; }
      timer = setTimeout(function () {
        fetch(url + '?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) { render(Array.isArray(data) ? data : (data.results || [])); })
          .catch(function () { drop.style.display = 'none'; });
      }, 260);
    });

    inp.addEventListener('blur', function () {
      setTimeout(function () { drop.style.display = 'none'; }, 180);
    });
  }

  attachAc('bienes_transp', urlBienes);
  attachAc('clave_unidad',  urlUnidad);
})();
</script>
