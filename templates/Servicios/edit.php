<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Servicio $servicio
 */
$objetoOpts = [
    '' => 'Seleccione motivo de impuesto',
    '01' => '01 — No objeto de impuesto',
    '02' => '02 — Sí objeto de impuesto',
    '03' => '03 — Sí objeto del impuesto y no obligado al desglose',
    '04' => '04 — Sí objeto del impuesto y no causa impuesto',
    '05' => '05 — Sí objeto del impuesto, IVA crédito PODEBI',
    '06' => '06 — Sí objeto del IVA, no traslado IVA',
    '07' => '07 — No traslado del IVA, sí desglose IEPS',
    '08' => '08 — No traslado del IVA, no desglose IEPS',
];
$oi = (string)($servicio->objetoImp ?? '');
?>
<style>
.alf *{box-sizing:border-box}
.alf{
  --o:#E8651A;--ol:#FDF0E8;--om:#F5C49A;
  --g:#2D7A4F;--b:#1A5EAB;--r:#C0392B;
  --dk:#1C1C1C;--md:#555;--sb:#888;--br:#E8E3DC;--bg:#FAF7F2;--cd:#fff;
  font-family:'Outfit','Segoe UI',sans-serif;
  width:100%;max-width:960px;margin:0 auto;padding:24px;
}
.alf-header{display:flex;align-items:center;gap:12px;margin-bottom:22px}
.alf-back{width:34px;height:34px;border-radius:9px;border:1px solid var(--br);
  background:#fff;display:inline-flex;align-items:center;justify-content:center;
  color:var(--md);text-decoration:none;font-size:13px;transition:all .14s;flex-shrink:0}
.alf-back:hover{background:var(--ol);color:var(--o);border-color:var(--om)}
.alf-title{font-family:'DM Serif Display',serif;font-size:24px;color:var(--dk)}
.alf-card{background:var(--cd);border:1px solid var(--br);border-radius:14px;overflow:hidden}
.alf-card-head{padding:16px 22px;border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between}
.alf-card-head-left{display:flex;align-items:center;gap:10px}
.alf-card-icon{width:36px;height:36px;border-radius:9px;background:var(--ol);
  display:flex;align-items:center;justify-content:center;color:var(--o);font-size:15px}
.alf-card-title{font-size:14px;font-weight:600;color:var(--dk)}
.alf-card-sub{font-size:12px;color:var(--sb)}
.alf-id-badge{font-size:11px;color:var(--sb);background:var(--bg);
  border:1px solid var(--br);border-radius:6px;padding:3px 9px}
.alf-body{padding:22px}
.alf-section{margin-bottom:22px}
.alf-section-label{font-size:10px;font-weight:600;color:var(--sb);text-transform:uppercase;
  letter-spacing:.08em;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--br)}
.alf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.alf-full{grid-column:1/-1}
.alf-field{display:flex;flex-direction:column;gap:5px}
.alf-label{font-size:12px;font-weight:500;color:var(--md)}
.alf-label .req{color:var(--r);margin-left:2px}
.alf-input,.alf-select{padding:9px 12px;border:1px solid var(--br);border-radius:9px;
  font-size:13px;font-family:'Outfit',sans-serif;color:var(--dk);background:#fff;
  outline:none;width:100%;transition:border-color .14s,box-shadow .14s}
.alf-input:focus,.alf-select:focus{border-color:var(--o);box-shadow:0 0 0 3px rgba(232,101,26,.12)}
.alf-input::placeholder{color:#bbb}
.alf-hint{font-size:11px;color:var(--sb);margin-top:2px}
.alf-ac-wrap{position:relative}
.alf-ac-results{
  position:absolute;top:calc(100% + 4px);left:0;right:0;
  background:#fff;border:1px solid var(--br);border-radius:10px;
  box-shadow:0 4px 20px rgba(0,0,0,.1);max-height:220px;overflow-y:auto;z-index:100;display:none;
}
.alf-ac-results.open{display:block}
.alf-ac-item{
  padding:9px 12px;font-size:13px;color:var(--dk);cursor:pointer;
  transition:background .12s;border-bottom:1px solid var(--bg);
}
.alf-ac-item:last-child{border-bottom:none}
.alf-ac-item:hover{background:var(--ol);color:var(--o)}
.alf-footer{display:flex;align-items:center;justify-content:flex-end;gap:10px;
  padding:16px 22px;border-top:1px solid var(--br);background:#FDFAF6}
.alf-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:9px;
  font-size:13px;font-weight:500;font-family:'Outfit',sans-serif;cursor:pointer;
  text-decoration:none;border:1px solid transparent;transition:all .15s}
.alf-btn-cancel{background:#fff;color:var(--md);border-color:var(--br)}
.alf-btn-cancel:hover{background:var(--bg);color:var(--o);border-color:var(--om)}
.alf-btn-save{background:var(--o);color:#fff;border-color:var(--o)}
.alf-btn-save:hover{background:#cf5715;border-color:#cf5715}
@media(max-width:640px){.alf-grid{grid-template-columns:1fr}.alf{padding:16px}}
</style>

<div class="alf">
  <div class="alf-header">
    <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'index']) ?>" class="alf-back" title="Volver"><i class="fas fa-arrow-left"></i></a>
    <h1 class="alf-title">Editar producto o servicio</h1>
  </div>

  <div class="alf-card">
    <div class="alf-card-head">
      <div class="alf-card-head-left">
        <div class="alf-card-icon"><i class="fas fa-box-open"></i></div>
        <div>
          <div class="alf-card-title"><?= h($servicio->descripcion ?? 'Servicio') ?></div>
          <div class="alf-card-sub">Actualizar datos del catálogo</div>
        </div>
      </div>
      <div class="alf-id-badge">ID #<?= (int)$servicio->id ?></div>
    </div>

    <?= $this->Form->create($servicio, ['id' => 'servicioForm']) ?>

    <div class="alf-body">
      <div class="alf-section">
        <div class="alf-section-label"><i class="fas fa-align-left" style="margin-right:5px"></i>General</div>
        <div class="alf-grid">
          <div class="alf-field alf-full">
            <label class="alf-label" for="descripcion">Descripción <span class="req">*</span></label>
            <input type="text" class="alf-input" id="descripcion" name="descripcion"
                   value="<?= h($servicio->descripcion ?? '') ?>" placeholder="Descripción" required>
          </div>
          <div class="alf-field">
            <label class="alf-label" for="numeroIdentificacion">Número de identificación</label>
            <input type="text" class="alf-input" id="numeroIdentificacion" name="numeroIdentificacion"
                   value="<?= h($servicio->numeroIdentificacion ?? '') ?>" placeholder="Opcional">
          </div>
          <div class="alf-field">
            <label class="alf-label" for="precio">Precio</label>
            <input type="number" class="alf-input" id="precio" name="precio" step="0.01" min="0"
                   value="<?= h($servicio->precio ?? '') ?>" placeholder="0.00">
          </div>
        </div>
      </div>

      <div class="alf-section" style="margin-bottom:0">
        <div class="alf-section-label"><i class="fas fa-barcode" style="margin-right:5px"></i>Clasificación SAT</div>
        <div class="alf-grid">
          <div class="alf-field alf-full">
            <label class="alf-label" for="claveSAT">Clave SAT</label>
            <div class="alf-ac-wrap">
              <input type="text" class="alf-input" id="claveSAT" name="claveSAT" autocomplete="off"
                     value="<?= h($servicio->claveSAT ?? '') ?>" placeholder="Buscar clave…">
              <div id="claveSATResults" class="alf-ac-results"></div>
            </div>
            <span class="alf-hint">Escribe para buscar en el catálogo</span>
          </div>
          <div class="alf-field alf-full">
            <label class="alf-label" for="unidadSAT">Unidad SAT</label>
            <div class="alf-ac-wrap">
              <input type="text" class="alf-input" id="unidadSAT" name="unidadSAT" autocomplete="off"
                     value="<?= h($servicio->unidadSAT ?? '') ?>" placeholder="Buscar unidad SAT…">
              <div id="unidadSATResults" class="alf-ac-results"></div>
            </div>
          </div>
          <div class="alf-field alf-full">
            <label class="alf-label" for="objetoImp">Objeto de impuesto</label>
            <select class="alf-select" id="objetoImp" name="objetoImp">
              <?php foreach ($objetoOpts as $val => $lab): ?>
                <option value="<?= h($val) ?>" <?= $oi === $val ? 'selected' : '' ?>><?= h($lab) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="alf-footer">
      <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'index']) ?>" class="alf-btn alf-btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
      <button type="submit" class="alf-btn alf-btn-save"><i class="fas fa-save"></i> Guardar cambios</button>
    </div>

    <?= $this->Form->end() ?>
  </div>
</div>

<script>
(function () {
  const urlClave = <?= json_encode($this->Url->build(['controller' => 'Servicios', 'action' => 'searchClaveSAT'])) ?>;
  const urlUnidad = <?= json_encode($this->Url->build(['controller' => 'Servicios', 'action' => 'searchUnidadSAT'])) ?>;

  function bindAutocomplete(inputId, boxId, fetchUrl) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(boxId);
    if (!input || !box) return;

    let controller;
    let timer;

    function close() {
      box.innerHTML = '';
      box.classList.remove('open');
    }

    function render(items) {
      if (!items.length) {
        close();
        return;
      }
      box.innerHTML = '';
      items.forEach(function (item) {
        const div = document.createElement('div');
        div.className = 'alf-ac-item';
        div.textContent = item.label || item.id;
        div.addEventListener('click', function () {
          input.value = item.id;
          close();
        });
        box.appendChild(div);
      });
      box.classList.add('open');
    }

    input.addEventListener('input', function () {
      const q = input.value.trim();
      if (timer) clearTimeout(timer);
      if (controller) controller.abort();
      if (q.length < 1) {
        close();
        return;
      }
      timer = setTimeout(async function () {
        try {
          controller = new AbortController();
          const res = await fetch(fetchUrl + '?q=' + encodeURIComponent(q), { signal: controller.signal });
          const json = await res.json();
          render(json.results || []);
        } catch (e) {}
      }, 300);
    });

    document.addEventListener('click', function (e) {
      if (!box.contains(e.target) && e.target !== input) close();
    });
  }

  bindAutocomplete('claveSAT', 'claveSATResults', urlClave);
  bindAutocomplete('unidadSAT', 'unidadSATResults', urlUnidad);
})();
</script>
