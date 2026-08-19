<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CatalogoMercancia> $mercancias
 */
function cmInitials(?string $text): string
{
    if (!$text) return '?';
    $parts = array_filter(explode(' ', trim($text)));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
    return strtoupper(mb_substr($parts[0] ?? '?', 0, 2));
}
?>
<style>
.cm *{box-sizing:border-box}
.cm{
  --t:#2BBFB3;--tl:#E6F7F6;--tm:#9DDDD9;
  --g:#2D7A4F;--b:#1A5EAB;--r:#C0392B;--am:#b45309;
  --dk:#1C1C1C;--md:#555;--sb:#888;--br:#E0EDED;--bg:#F4FBFB;--cd:#fff;
  font-family:'Outfit','Segoe UI',sans-serif;
  padding:24px;
}

.cm-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.cm-header-left{display:flex;align-items:center;gap:12px}
.cm-title{font-family:'DM Serif Display',serif;font-size:24px;color:var(--dk);line-height:1.2}
.cm-count{display:inline-flex;align-items:center;justify-content:center;
  background:var(--tl);color:var(--t);font-size:11px;font-weight:600;
  border-radius:20px;padding:2px 10px;border:1px solid var(--tm)}
.cm-actions{display:flex;gap:8px;flex-wrap:wrap}

.cm-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:8px 16px;border-radius:9px;font-size:13px;font-weight:500;
  font-family:'Outfit',sans-serif;cursor:pointer;text-decoration:none;
  border:1px solid transparent;transition:all .15s;white-space:nowrap;
}
.cm-btn-primary{background:var(--t);color:#fff;border-color:var(--t)}
.cm-btn-primary:hover{background:#22a89d;border-color:#22a89d;color:#fff}

.cm-card{background:var(--cd);border:1px solid var(--br);border-radius:14px;overflow:hidden}
.cm-card-top{padding:16px 20px;border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.cm-card-label{font-size:12px;font-weight:500;color:var(--sb)}
.cm-search-wrap{position:relative}
.cm-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sb);font-size:12px}
#cmSearch{
  padding:7px 10px 7px 30px;border:1px solid var(--br);border-radius:8px;
  font-size:13px;font-family:'Outfit',sans-serif;outline:none;width:220px;
  transition:border-color .14s,box-shadow .14s;
}
#cmSearch:focus{border-color:var(--t);box-shadow:0 0 0 3px rgba(43,191,179,.14)}

.cm-table-wrap{overflow-x:auto}
.cm-table{width:100%;border-collapse:collapse;font-size:13px}
.cm-table thead tr{border-bottom:1px solid var(--br)}
.cm-table th{
  padding:10px 14px;text-align:left;font-size:11px;font-weight:500;
  color:var(--sb);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;
}
.cm-table tbody tr{border-bottom:1px solid var(--bg);transition:background .12s}
.cm-table tbody tr:last-child{border-bottom:none}
.cm-table tbody tr:hover{background:#F0FAFA}
.cm-table td{padding:11px 14px;color:var(--dk);vertical-align:middle;white-space:nowrap}

.cm-avatar{
  width:34px;height:34px;border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:600;color:#fff;flex-shrink:0;
}
.cm-name-cell{display:flex;align-items:center;gap:10px}
.cm-name-text{font-weight:500;color:var(--dk)}
.cm-sub{font-size:11px;color:var(--sb);margin-top:1px;font-family:monospace}
.cm-pill{background:var(--bg);color:var(--md);border:1px solid var(--br);
  display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:500}
.cm-pill-teal{background:var(--tl);color:var(--t);border-color:var(--tm)}
.cm-pill-warn{background:#FEF3C7;color:var(--am);border-color:#FDE68A}

.cm-btn-icon{
  width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;
  justify-content:center;border:1px solid transparent;cursor:pointer;
  font-size:13px;text-decoration:none;transition:all .15s;
}
.cm-btn-edit{background:#EEF3FB;color:var(--b);border-color:#d0dff5}
.cm-btn-edit:hover{background:var(--b);color:#fff;border-color:var(--b)}
.cm-btn-del{background:#FDE8E8;color:var(--r);border-color:#f5c8c8}
.cm-btn-del:hover{background:var(--r);color:#fff;border-color:var(--r)}

.cm-pagination{display:flex;align-items:center;justify-content:space-between;
  padding:12px 20px;border-top:1px solid var(--br);flex-wrap:wrap;gap:8px}
.cm-page-info{font-size:12px;color:var(--sb)}

.cm-modal-bg{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);
  z-index:500;align-items:center;justify-content:center;
}
.cm-modal-bg.open{display:flex}
.cm-modal{
  background:#fff;border-radius:14px;padding:28px 28px 22px;
  max-width:380px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.14);
}
.cm-modal-icon{width:48px;height:48px;border-radius:50%;background:#FDE8E8;
  display:flex;align-items:center;justify-content:center;font-size:22px;
  margin:0 auto 14px;color:var(--r)}
.cm-modal-title{font-size:16px;font-weight:600;color:var(--dk);text-align:center;margin-bottom:6px}
.cm-modal-msg{font-size:13px;color:var(--sb);text-align:center;margin-bottom:20px}
.cm-modal-btns{display:flex;gap:8px;justify-content:center}
.cm-btn-secondary{background:#fff;color:#555;border:1px solid #E0EDED}
.cm-btn-secondary:hover{background:var(--tl);color:var(--t);border-color:var(--tm)}

@media(max-width:700px){
  .cm-title{font-size:20px}
  .cm{padding:16px}
  #cmSearch{width:160px}
}
</style>

<div class="cm">

  <div class="cm-header">
    <div class="cm-header-left">
      <h1 class="cm-title">Catálogo de mercancías</h1>
      <span class="cm-count" id="cmTotalBadge">… registros</span>
    </div>
    <div class="cm-actions">
      <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="cm-btn cm-btn-primary">
        <i class="fas fa-plus"></i> Nueva mercancía
      </a>
    </div>
  </div>

  <div class="cm-card">
    <div class="cm-card-top">
      <span class="cm-card-label">Bienes frecuentes para Carta Porte autotransporte</span>
      <div class="cm-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="cmSearch" placeholder="Buscar…">
      </div>
    </div>

    <div class="cm-table-wrap">
      <table class="cm-table" id="cmTable">
        <thead>
          <tr>
            <th>Descripción</th>
            <th>BienesTransp</th>
            <th>Clave Unidad</th>
            <th>Peso ref. (kg)</th>
            <th>Material peligroso</th>
            <th style="text-align:center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $avatarColors = ['#2BBFB3','#2D7A4F','#1A5EAB','#6C3AC4','#0891b2','#b45309','#C0392B'];
          $i = 0;
          foreach ($mercancias as $m):
              $color = $avatarColors[$i++ % count($avatarColors)];
              $ini = cmInitials($m->descripcion ?? '');
          ?>
          <tr>
            <td>
              <div class="cm-name-cell">
                <div class="cm-avatar" style="background:<?= $color ?>"><?= h($ini) ?></div>
                <div>
                  <div class="cm-name-text"><?= h($m->descripcion ?? '—') ?></div>
                  <?php if ($m->bienes_transp): ?>
                    <div class="cm-sub"><?= h($m->bienes_transp) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <?php if ($m->bienes_transp): ?>
                <span class="cm-pill cm-pill-teal"><?= h($m->bienes_transp) ?></span>
              <?php else: ?>
                <span style="color:#ccc">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($m->clave_unidad): ?>
                <span class="cm-pill"><?= h($m->clave_unidad) ?></span>
              <?php else: ?>
                <span style="color:#ccc">—</span>
              <?php endif; ?>
            </td>
            <td style="font-family:monospace;font-size:12px">
              <?= $m->peso_en_kg !== null ? h(number_format((float)$m->peso_en_kg, 3)) : '<span style="color:#ccc">—</span>' ?>
            </td>
            <td>
              <?php if ($m->material_peligroso): ?>
                <span class="cm-pill cm-pill-warn">⚠ Sí<?= $m->cve_material_peligroso ? ' · ' . h($m->cve_material_peligroso) : '' ?></span>
              <?php else: ?>
                <span style="color:#bbb;font-size:12px">No</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <div style="display:flex;gap:6px;justify-content:center">
                <a href="<?= $this->Url->build(['action' => 'edit', $m->id]) ?>"
                   class="cm-btn-icon cm-btn-edit" title="Editar">
                  <i class="fas fa-pencil-alt"></i>
                </a>
                <button type="button"
                   class="cm-btn-icon cm-btn-del" title="Eliminar"
                   onclick="cmConfirmDelete(<?= (int)$m->id ?>, '<?= h(addslashes($m->descripcion ?? '')) ?>')">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="cm-pagination">
      <span class="cm-page-info" id="cmInfo"></span>
      <div id="cmPager" style="display:flex;gap:4px"></div>
    </div>
  </div>

</div>

<form id="cm-del-form" method="post" action="" style="display:none">
  <input type="hidden" name="_csrfToken" value="<?= h($this->request->getAttribute('csrfToken') ?? '') ?>">
</form>

<div class="cm-modal-bg" id="cmDeleteModal">
  <div class="cm-modal">
    <div class="cm-modal-icon"><i class="fas fa-trash-alt"></i></div>
    <div class="cm-modal-title">Eliminar mercancía</div>
    <div class="cm-modal-msg" id="cmDeleteMsg">¿Eliminar este registro?</div>
    <div class="cm-modal-btns">
      <button type="button" class="cm-btn cm-btn-secondary" onclick="cmCloseModal()">Cancelar</button>
      <button type="button" class="cm-btn cm-btn-primary" style="background:#C0392B;border-color:#C0392B" onclick="cmSubmitDelete()">
        Sí, eliminar
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var search   = document.getElementById('cmSearch');
  var rows     = Array.from(document.querySelectorAll('#cmTable tbody tr'));
  var info     = document.getElementById('cmInfo');
  var pager    = document.getElementById('cmPager');
  var badge    = document.getElementById('cmTotalBadge');
  var PAGE     = 15;
  var page     = 0;
  var filtered = rows;
  var pendingDeleteId = null;

  if (badge) badge.textContent = rows.length + ' registros';

  function render() {
    var start = page * PAGE;
    rows.forEach(function(r){ r.style.display = 'none'; });
    filtered.slice(start, start + PAGE).forEach(function(r){ r.style.display = ''; });
    var end = Math.min(start + PAGE, filtered.length);
    info.textContent = filtered.length
      ? 'Mostrando ' + (start + 1) + '–' + end + ' de ' + filtered.length
      : 'Sin resultados';
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
      btn.style.cssText = 'width:30px;height:30px;border-radius:6px;border:1px solid '
        + (i === page ? '#2BBFB3' : '#E0EDED') + ';background:'
        + (i === page ? '#2BBFB3' : '#fff') + ';color:'
        + (i === page ? '#fff' : '#555') + ';font-size:12px;cursor:pointer;font-family:Outfit,sans-serif';
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

  render();

  window.cmConfirmDelete = function(id, name) {
    pendingDeleteId = id;
    document.getElementById('cmDeleteMsg').textContent =
      '¿Eliminar "' + name + '"? Esta acción no se puede deshacer.';
    document.getElementById('cmDeleteModal').classList.add('open');
  };

  window.cmCloseModal = function() {
    pendingDeleteId = null;
    document.getElementById('cmDeleteModal').classList.remove('open');
  };

  window.cmSubmitDelete = function() {
    if (!pendingDeleteId) return;
    var f = document.getElementById('cm-del-form');
    f.action = '<?= $this->Url->build(['action' => 'delete']) ?>/' + pendingDeleteId;
    f.submit();
  };

  document.getElementById('cmDeleteModal').addEventListener('click', function(e) {
    if (e.target === this) cmCloseModal();
  });
})();
</script>
