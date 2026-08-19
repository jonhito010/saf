<?php
/**
 * Servicios — Detalle
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Servicio $servicio
 */
$parts = array_filter(explode(' ', trim((string)($servicio->descripcion ?? ''))));
$ini = count($parts) >= 2
    ? strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1))
    : strtoupper(mb_substr($parts[0] ?? '?', 0, 2));

$objetoTxt = match ((string)($servicio->objetoImp ?? '')) {
    '01' => '01 — No objeto de impuesto',
    '02' => '02 — Sí objeto de impuesto',
    '03' => '03 — Sí objeto del impuesto y no obligado al desglose',
    '04' => '04 — Sí objeto del impuesto y no causa impuesto',
    '05' => '05 — Sí objeto del impuesto, IVA crédito PODEBI',
    '06' => '06 — Sí objeto del IVA, no traslado IVA',
    '07' => '07 — No traslado del IVA, sí desglose IEPS',
    '08' => '08 — No traslado del IVA, no desglose IEPS',
    default => '—',
};
?>
<style>
.svv *{box-sizing:border-box}
.svv{
  --o:#E8651A;--ol:#FDF0E8;--om:#F5C49A;
  --g:#2D7A4F;--b:#1A5EAB;--r:#C0392B;
  --dk:#1C1C1C;--md:#555;--sb:#888;--br:#E8E3DC;--bg:#FAF7F2;--cd:#fff;
  font-family:'Outfit','Segoe UI',sans-serif;padding:24px;
}
.svv-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.svv-header-left{display:flex;align-items:center;gap:12px}
.svv-back{width:34px;height:34px;border-radius:9px;border:1px solid var(--br);
  background:#fff;display:inline-flex;align-items:center;justify-content:center;
  color:var(--md);text-decoration:none;font-size:13px;transition:all .14s;flex-shrink:0}
.svv-back:hover{background:var(--ol);color:var(--o);border-color:var(--om)}
.svv-title{font-family:'DM Serif Display',serif;font-size:24px;color:var(--dk)}
.svv-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.svv-actions form{display:inline;margin:0;padding:0;border:0;background:transparent}
.svv-actions form button{font:inherit}
.svv-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:9px;
  font-size:13px;font-weight:500;font-family:'Outfit',sans-serif;cursor:pointer;
  text-decoration:none;border:1px solid transparent;transition:all .15s}
.svv-btn-edit{background:#EEF3FB;color:var(--b);border-color:#d0dff5}
.svv-btn-edit:hover{background:var(--b);color:#fff}
.svv-btn-del{background:#FDE8E8;color:var(--r);border-color:#f5c8c8}
.svv-btn-del:hover{background:var(--r);color:#fff}

.svv-layout{display:grid;grid-template-columns:280px 1fr;gap:18px;align-items:start}
.svv-card{background:var(--cd);border:1px solid var(--br);border-radius:14px;overflow:hidden}
.svv-profile{padding:28px 20px;text-align:center;border-bottom:1px solid var(--br)}
.svv-avatar{width:72px;height:72px;border-radius:50%;display:inline-flex;align-items:center;
  justify-content:center;font-size:24px;font-weight:600;color:#fff;margin-bottom:12px;background:var(--o)}
.svv-name{font-size:16px;font-weight:600;color:var(--dk);margin-bottom:8px}
.svv-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:600;
  background:#EEF3FB;color:var(--b);border:1px solid #d0dff5}
.svv-precio{font-size:20px;font-weight:600;color:var(--g);margin-top:10px}

.svv-info-list{padding:16px 20px}
.svv-info-row{display:flex;align-items:center;justify-content:space-between;
  padding:8px 0;border-bottom:1px solid var(--bg);font-size:13px}
.svv-info-row:last-child{border-bottom:none}
.svv-info-key{color:var(--sb);font-size:12px;display:flex;align-items:center;gap:6px}
.svv-info-key i{width:14px;text-align:center;font-size:11px}
.svv-info-val{font-weight:500;color:var(--dk);text-align:right;max-width:55%;font-family:monospace;font-size:12px}

.svv-right{display:flex;flex-direction:column;gap:16px}
.svv-section-card{background:var(--cd);border:1px solid var(--br);border-radius:14px;overflow:hidden}
.svv-section-head{padding:14px 18px;border-bottom:1px solid var(--br);display:flex;align-items:center;gap:8px}
.svv-section-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px}
.svv-section-title{font-size:13px;font-weight:600;color:var(--dk)}
.svv-section-body{padding:16px 18px;font-size:13px;color:var(--dk);line-height:1.55}
.svv-section-body p{margin:0 0 8px}

@media(max-width:860px){
  .svv-layout{grid-template-columns:1fr}
  .svv{padding:16px}
}
</style>

<div class="svv">
  <div class="svv-header">
    <div class="svv-header-left">
      <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'index']) ?>" class="svv-back" title="Volver"><i class="fas fa-arrow-left"></i></a>
      <h1 class="svv-title">Producto o servicio</h1>
    </div>
    <div class="svv-actions">
      <a href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'edit', $servicio->id]) ?>" class="svv-btn svv-btn-edit">
        <i class="fas fa-pencil-alt"></i> Editar
      </a>
      <?= $this->Form->postLink(
          '<i class="fas fa-trash-alt"></i> Eliminar',
          ['action' => 'delete', $servicio->id],
          ['escape' => false, 'class' => 'svv-btn svv-btn-del', 'confirm' => '¿Eliminar este registro?']
      ) ?>
    </div>
  </div>

  <div class="svv-layout">
    <div class="svv-card">
      <div class="svv-profile">
        <div class="svv-avatar"><?= h($ini) ?></div>
        <div class="svv-name"><?= h($servicio->descripcion ?? '—') ?></div>
        <span class="svv-badge"><i class="fas fa-barcode" style="margin-right:4px;opacity:.85"></i>SAT</span>
        <div class="svv-precio">
          $<?= $servicio->precio === null || $servicio->precio === '' ? '0.00' : number_format((float)$servicio->precio, 2, '.', ',') ?>
        </div>
      </div>
      <div class="svv-info-list">
        <div class="svv-info-row">
          <span class="svv-info-key"><i class="fas fa-hashtag"></i>ID</span>
          <span class="svv-info-val">#<?= (int)$servicio->id ?></span>
        </div>
        <div class="svv-info-row">
          <span class="svv-info-key"><i class="fas fa-fingerprint"></i>Identificación</span>
          <span class="svv-info-val"><?= $servicio->numeroIdentificacion ? h($servicio->numeroIdentificacion) : '—' ?></span>
        </div>
        <div class="svv-info-row">
          <span class="svv-info-key"><i class="fas fa-key"></i>Clave SAT</span>
          <span class="svv-info-val"><?= $servicio->claveSAT ? h($servicio->claveSAT) : '—' ?></span>
        </div>
        <div class="svv-info-row">
          <span class="svv-info-key"><i class="fas fa-ruler"></i>Unidad SAT</span>
          <span class="svv-info-val"><?= $servicio->unidadSAT ? h($servicio->unidadSAT) : '—' ?></span>
        </div>
      </div>
    </div>

    <div class="svv-right">
      <div class="svv-section-card">
        <div class="svv-section-head">
          <div class="svv-section-icon" style="background:#FDF0E8;color:#E8651A"><i class="fas fa-percent"></i></div>
          <span class="svv-section-title">Objeto de impuesto</span>
        </div>
        <div class="svv-section-body">
          <p><?= h($objetoTxt) ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
