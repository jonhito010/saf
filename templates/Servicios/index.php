<?php
/**
 * Servicios — Lista
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Servicio> $servicios
 */

function svObjetoImp(?string $objetoImp): string
{
    return match ($objetoImp) {
        '01' => '01 — No objeto de impuesto',
        '02' => '02 — Sí objeto de impuesto',
        '03' => '03 — Objeto impuesto, sin desglose',
        '04' => '04 — Objeto impuesto, no causa impuesto',
        '05' => '05 — IVA crédito PODEBI',
        '06' => '06 — Objeto IVA, no traslado IVA',
        '07' => '07 — No traslado IVA, desglose IEPS',
        '08' => '08 — No traslado IVA, sin desglose IEPS',
        default => '',
    };
}

function svInitials(?string $text): string
{
    if (!$text) {
        return '?';
    }
    $parts = array_filter(explode(' ', trim($text)));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }

    return strtoupper(mb_substr($parts[0] ?? '?', 0, 2));
}
?>
<style>
.sv *{box-sizing:border-box}
.sv{
  --o:#E8651A;--ol:#FDF0E8;--om:#F5C49A;
  --g:#2D7A4F;--b:#1A5EAB;--r:#C0392B;--pu:#6C3AC4;
  --dk:#1C1C1C;--md:#555;--sb:#888;--br:#E8E3DC;--bg:#FAF7F2;--cd:#fff;
  font-family:'Outfit','Segoe UI',sans-serif;
  padding:24px;
}

.sv-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.sv-header-left{display:flex;align-items:center;gap:12px}
.sv-title{font-family:'DM Serif Display',serif;font-size:24px;color:var(--dk);line-height:1.2}
.sv-count{display:inline-flex;align-items:center;justify-content:center;
  background:var(--ol);color:var(--o);font-size:11px;font-weight:600;
  border-radius:20px;padding:2px 10px;border:1px solid var(--om)}
.sv-actions{display:flex;gap:8px;flex-wrap:wrap}

.sv-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:8px 16px;border-radius:9px;font-size:13px;font-weight:500;
  font-family:'Outfit',sans-serif;cursor:pointer;text-decoration:none;
  border:1px solid transparent;transition:all .15s;white-space:nowrap;
}
.sv-btn-primary{background:var(--o);color:#fff;border-color:var(--o)}
.sv-btn-primary:hover{background:#cf5715;border-color:#cf5715;color:#fff}

.sv-card{background:var(--cd);border:1px solid var(--br);border-radius:14px;overflow:hidden}
.sv-card-top{padding:16px 20px;border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.sv-card-label{font-size:12px;font-weight:500;color:var(--sb)}
.sv-search-wrap{position:relative}
.sv-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sb);font-size:12px}
#svSearch{
  padding:7px 10px 7px 30px;border:1px solid var(--br);border-radius:8px;
  font-size:13px;font-family:'Outfit',sans-serif;outline:none;width:220px;
  transition:border-color .14s,box-shadow .14s;
}
#svSearch:focus{border-color:var(--o);box-shadow:0 0 0 3px rgba(232,101,26,.12)}

.sv-table-wrap{overflow-x:auto}
.sv-table{width:100%;border-collapse:collapse;font-size:13px}
.sv-table thead tr{border-bottom:1px solid var(--br)}
.sv-table th{
  padding:10px 14px;text-align:left;font-size:11px;font-weight:500;
  color:var(--sb);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;
}
.sv-table tbody tr{border-bottom:1px solid var(--bg);transition:background .12s}
.sv-table tbody tr:last-child{border-bottom:none}
.sv-table tbody tr:hover{background:#FDF9F5}
.sv-table td{padding:11px 14px;color:var(--dk);vertical-align:middle;white-space:nowrap}

.sv-avatar{
  width:34px;height:34px;border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:600;color:#fff;flex-shrink:0;
}
.sv-name-cell{display:flex;align-items:center;gap:10px}
.sv-name-text{font-weight:500;color:var(--dk)}
.sv-sub{font-size:11px;color:var(--sb);margin-top:1px;font-family:monospace}
.sv-pill{background:var(--bg);color:var(--md);border:1px solid var(--br);
  display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:500}

.sv-btn-icon{
  width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;
  justify-content:center;border:1px solid transparent;cursor:pointer;
  font-size:13px;text-decoration:none;transition:all .15s;
}
.sv-btn-edit{background:#EEF3FB;color:var(--b);border-color:#d0dff5}
.sv-btn-edit:hover{background:var(--b);color:#fff;border-color:var(--b)}
.sv-btn-del{background:#FDE8E8;color:var(--r);border-color:#f5c8c8}
.sv-btn-del:hover{background:var(--r);color:#fff;border-color:var(--r)}

.sv-pagination{display:flex;align-items:center;justify-content:space-between;
  padding:12px 20px;border-top:1px solid var(--br);flex-wrap:wrap;gap:8px}
.sv-page-info{font-size:12px;color:var(--sb)}

.sv-modal-bg{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);
  z-index:500;align-items:center;justify-content:center;
}
.sv-modal-bg.open{display:flex}
.sv-modal{
  background:#fff;border-radius:14px;padding:28px 28px 22px;
  max-width:380px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.14);
}
.sv-modal-icon{width:48px;height:48px;border-radius:50%;background:#FDE8E8;
  display:flex;align-items:center;justify-content:center;font-size:22px;
  margin:0 auto 14px;color:var(--r)}
.sv-modal-title{font-size:16px;font-weight:600;color:var(--dk);text-align:center;margin-bottom:6px}
.sv-modal-msg{font-size:13px;color:var(--sb);text-align:center;margin-bottom:20px}
.sv-modal-btns{display:flex;gap:8px;justify-content:center}
.sv-modal-bg{--md:#555;--o:#E8651A;--om:#F5C49A;--bg:#FAF7F2}
.sv-btn-secondary{background:#fff;color:#555;border:1px solid #E8E3DC}
.sv-btn-secondary:hover{background:#FAF7F2;color:#E8651A;border-color:#F5C49A}

@media(max-width:700px){
  .sv-title{font-size:20px}
  .sv{padding:16px}
  #svSearch{width:160px}
}
</style>

<div class="sv">

  <div class="sv-header">
    <div class="sv-header-left">
      <h1 class="sv-title">Productos y servicios</h1>
      <span class="sv-count"><?= count($servicios) ?> registros</span>
    </div>
    <div class="sv-actions">
      <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'add']) ?>" class="sv-btn sv-btn-primary">
        <i class="fas fa-plus"></i> Nuevo producto o servicio
      </a>
    </div>
  </div>

  <div class="sv-card">
    <div class="sv-card-top">
      <span class="sv-card-label">Catálogo para facturación (clave y unidad SAT)</span>
      <div class="sv-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="svSearch" placeholder="Buscar…">
      </div>
    </div>

    <div class="sv-table-wrap">
      <table class="sv-table" id="svTable">
        <thead>
          <tr>
            <th>Descripción</th>
            <th>Núm. identificación</th>
            <th>Clave SAT</th>
            <th>Precio</th>
            <th>Unidad SAT</th>
            <th>Objeto impuesto</th>
            <th style="text-align:center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $avatarColors = ['#E8651A','#2D7A4F','#1A5EAB','#C0392B','#6C3AC4','#0891b2','#b45309'];
          foreach ($servicios as $i => $servicio):
              $color = $avatarColors[$i % count($avatarColors)];
              $ini = svInitials($servicio->descripcion ?? '');
              $objLabel = svObjetoImp($servicio->objetoImp ?? null);
          ?>
          <tr>
            <td>
              <div class="sv-name-cell">
                <div class="sv-avatar" style="background:<?= $color ?>"><?= h($ini) ?></div>
                <div>
                  <div class="sv-name-text"><?= h($servicio->descripcion ?? '—') ?></div>
                  <?php if (!empty($servicio->claveSAT)): ?>
                    <div class="sv-sub" title="Clave SAT"><?= h($servicio->claveSAT) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= $servicio->numeroIdentificacion ? h($servicio->numeroIdentificacion) : '<span style="color:#ccc">—</span>' ?></td>
            <td><?= $servicio->claveSAT ? '<span class="sv-pill">' . h($servicio->claveSAT) . '</span>' : '<span style="color:#ccc">—</span>' ?></td>
            <td><strong>$<?= $servicio->precio === null || $servicio->precio === '' ? '0.00' : number_format((float)$servicio->precio, 2, '.', ',') ?></strong></td>
            <td><?= $servicio->unidadSAT ? h($servicio->unidadSAT) : '<span style="color:#ccc">—</span>' ?></td>
            <td>
              <?php if ($servicio->objetoImp): ?>
                <span class="sv-pill" title="<?= h($objLabel) ?>"><?= h($servicio->objetoImp) ?></span>
              <?php else: ?>
                <span style="color:#ccc">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <div style="display:flex;gap:6px;justify-content:center">
                <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'edit', $servicio->id]) ?>"
                   class="sv-btn-icon sv-btn-edit" title="Editar">
                  <i class="fas fa-pencil-alt"></i>
                </a>
                <button type="button"
                   class="sv-btn-icon sv-btn-del" title="Eliminar"
                   onclick="svConfirmDelete(<?= (int)$servicio->id ?>, '<?= h(addslashes($servicio->descripcion ?? '')) ?>')">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="sv-pagination">
      <span class="sv-page-info" id="svInfo"></span>
      <div id="svPager" style="display:flex;gap:4px"></div>
    </div>
  </div>

</div>

<form id="sv-del-form" method="post" action="" style="display:none">
  <input type="hidden" name="_csrfToken" value="<?= h($this->request->getAttribute('csrfToken') ?? '') ?>">
</form>

<div class="sv-modal-bg" id="svDeleteModal">
  <div class="sv-modal">
    <div class="sv-modal-icon"><i class="fas fa-trash-alt"></i></div>
    <div class="sv-modal-title">Eliminar servicio</div>
    <div class="sv-modal-msg" id="svDeleteMsg">¿Eliminar este registro?</div>
    <div class="sv-modal-btns">
      <button type="button" class="sv-btn sv-btn-secondary" onclick="svCloseModal()">Cancelar</button>
      <button type="button" class="sv-btn sv-btn-primary" style="background:#C0392B;border-color:#C0392B;color:#fff" onclick="svSubmitDelete()">
        Sí, eliminar
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var search = document.getElementById('svSearch');
  var rows   = Array.from(document.querySelectorAll('#svTable tbody tr'));
  var info   = document.getElementById('svInfo');
  var pager  = document.getElementById('svPager');
  var PAGE   = 15;
  var page   = 0;
  var filtered = rows;
  var pendingDeleteId = null;

  var deleteBaseUrl = <?= json_encode($this->Url->build(['controller' => 'Servicios', 'action' => 'delete'])) ?>;

  function render() {
    var start = page * PAGE;
    rows.forEach(function(r){ r.style.display = 'none'; });
    filtered.slice(start, start + PAGE).forEach(function(r){ r.style.display = ''; });
    info.textContent = 'Mostrando ' + (start + 1) + '–' + Math.min(start + PAGE, filtered.length) + ' de ' + filtered.length;
    buildPager();
  }

  function buildPager() {
    pager.innerHTML = '';
    var total = Math.ceil(filtered.length / PAGE);
    if (total <= 1) return;
    for (var i = 0; i < total; i++) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = i + 1;
      btn.style.cssText = 'width:30px;height:30px;border-radius:6px;border:1px solid ' +
        (i === page ? '#E8651A' : '#E8E3DC') + ';background:' +
        (i === page ? '#E8651A' : '#fff') + ';color:' +
        (i === page ? '#fff' : '#555') + ';font-size:12px;cursor:pointer;font-family:Outfit,sans-serif';
      (function(idx){ btn.addEventListener('click', function(){ page = idx; render(); }); })(i);
      pager.appendChild(btn);
    }
  }

  search.addEventListener('input', function () {
    var q = search.value.trim().toLowerCase();
    filtered = rows.filter(function(r){ return r.textContent.toLowerCase().includes(q); });
    page = 0;
    render();
  });

  filtered = rows;
  render();

  window.svConfirmDelete = function(id, name) {
    pendingDeleteId = id;
    document.getElementById('svDeleteMsg').textContent =
      '¿Eliminar "' + name + '"? Esta acción no se puede deshacer.';
    document.getElementById('svDeleteModal').classList.add('open');
  };

  window.svCloseModal = function() {
    pendingDeleteId = null;
    document.getElementById('svDeleteModal').classList.remove('open');
  };

  window.svSubmitDelete = function() {
    if (!pendingDeleteId) return;
    var f = document.getElementById('sv-del-form');
    f.action = deleteBaseUrl + '/' + pendingDeleteId;
    f.submit();
  };

  document.getElementById('svDeleteModal').addEventListener('click', function(e) {
    if (e.target === this) svCloseModal();
  });
})();
</script>
