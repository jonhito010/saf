<?php
/**
 * Auditoría simple — actividad reciente.
 *
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $eventos
 * @var array{periodo: string, tipo: string, empresa_id: int|null, q: string} $filtros
 * @var array<int, string> $empresasFiltro
 * @var array<string, string> $periodos
 * @var array<string, string> $tipos
 */

$badgeCat = static function (string $cat): string {
    return match ($cat) {
        'acceso' => '<span class="badge badge-navy">Acceso</span>',
        'operador' => '<span class="badge badge-teal">Operador</span>',
        'cambio' => '<span class="badge badge-amber">Cambio</span>',
        default => '<span class="badge badge-gray">Sistema</span>',
    };
};

$fmtFecha = static function (string $raw): string {
    if ($raw === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($raw);

        return $dt->format('d/m/Y H:i');
    } catch (Throwable) {
        return $raw;
    }
};
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Auditoría</h1>
    <p>Quién hizo qué y cuándo — sin códigos técnicos</p>
  </div>
</div>

<form class="panel" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" style="margin-bottom:16px;padding:16px 20px">
  <div class="empresa-form-grid" style="align-items:end">
    <div>
      <label class="alcance-empresa-label" for="aud-periodo">Periodo</label>
      <select name="periodo" id="aud-periodo" class="alcance-empresa-select" onchange="this.form.submit()">
        <?php foreach ($periodos as $val => $label): ?>
          <option value="<?= h($val) ?>"<?= ($filtros['periodo'] ?? '') === $val ? ' selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="alcance-empresa-label" for="aud-tipo">Qué ver</label>
      <select name="tipo" id="aud-tipo" class="alcance-empresa-select" onchange="this.form.submit()">
        <?php foreach ($tipos as $val => $label): ?>
          <option value="<?= h($val) ?>"<?= ($filtros['tipo'] ?? '') === $val ? ' selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (count($empresasFiltro) > 1): ?>
    <div>
      <label class="alcance-empresa-label" for="aud-empresa">Empresa</label>
      <select name="empresa_id" id="aud-empresa" class="alcance-empresa-select" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($empresasFiltro as $eid => $enombre): ?>
          <option value="<?= (int)$eid ?>"<?= (int)($filtros['empresa_id'] ?? 0) === (int)$eid ? ' selected' : '' ?>><?= h($enombre) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div style="grid-column: span 2">
      <label class="alcance-empresa-label" for="aud-q">Buscar</label>
      <input type="search" name="q" id="aud-q" value="<?= h($filtros['q'] ?? '') ?>"
             placeholder="Persona, acción o detalle…" class="alcance-empresa-select" style="width:100%">
    </div>
    <div style="display:flex;gap:8px">
      <button type="submit" class="btn btn-primary">Buscar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Limpiar</a>
    </div>
  </div>
</form>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Actividad reciente</div>
    <div class="panel-action"><?= count($eventos) ?> evento<?= count($eventos) === 1 ? '' : 's' ?></div>
  </div>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Cuándo</th>
          <th>Qué pasó</th>
          <th>Quién</th>
          <th>Detalle</th>
          <th>Tipo</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($eventos === []): ?>
          <tr>
            <td colspan="5" style="text-align:center;color:var(--text3);padding:28px">
              No hay actividad en este periodo.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($eventos as $ev): ?>
            <tr>
              <td class="td-mono" data-label="Cuándo"><?= h($fmtFecha((string)($ev['cuando'] ?? ''))) ?></td>
              <td data-label="Qué pasó">
                <strong><?= h($ev['titulo'] ?? '') ?></strong>
                <?php if (!empty($ev['empresa'])): ?>
                  <div style="font-size:12px;color:var(--text3)"><?= h($ev['empresa']) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Quién"><?= h($ev['quien'] ?? '—') ?></td>
              <td data-label="Detalle" style="max-width:320px"><?= h($ev['detalle'] ?? '—') ?></td>
              <td data-label="Tipo"><?= $badgeCat((string)($ev['categoria'] ?? 'sistema')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
