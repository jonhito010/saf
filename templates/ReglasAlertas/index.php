<?php
/**
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $reglas
 * @var array<int, string> $etiquetas
 */

$badgeNivel = static function (?string $nivel): string {
    return match ($nivel) {
        'critico' => '<span class="badge badge-red">Crítico</span>',
        'advertencia' => '<span class="badge badge-amber">Advertencia</span>',
        default => '<span class="badge badge-navy">Info</span>',
    };
};
$etiquetas = $etiquetas ?? [];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Reglas de alertas</h1>
    <p>Umbrales de aviso por tipo de documento (o globales)</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">Nueva regla</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Catálogo de reglas</div>
    <div class="panel-action"><?= count($reglas) ?> regla<?= count($reglas) === 1 ? '' : 's' ?></div>
  </div>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Días antes</th>
          <th>Nombre</th>
          <th>Aplica a</th>
          <th>Enviar alerta</th>
          <th>Urgencia</th>
          <th>Activa</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($reglas === []): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:20px">No hay reglas configuradas.</td></tr>
        <?php else: ?>
          <?php foreach ($reglas as $r): ?>
            <tr>
              <td data-label="Días"><?= (int)($r['dias_antes'] ?? 0) ?></td>
              <td data-label="Nombre"><strong><?= h($r['nombre'] ?? '') ?></strong></td>
              <td data-label="Aplica a"><?= h($etiquetas[(int)$r['id']] ?? 'Todos los documentos') ?></td>
              <td data-label="Enviar"><?= (int)($r['enviar_alerta'] ?? 0) === 1 ? 'Sí' : 'No' ?></td>
              <td data-label="Urgencia"><?= $badgeNivel($r['nivel_urgencia'] ?? null) ?></td>
              <td data-label="Activa">
                <?php if ((int)($r['activo'] ?? 0) === 1): ?>
                  <span class="badge badge-green">Activa</span>
                <?php else: ?>
                  <span class="badge badge-gray">Inactiva</span>
                <?php endif; ?>
              </td>
              <td data-label="Acciones">
                <a class="btn btn-ghost" style="font-size:11px;padding:5px 10px"
                   href="<?= $this->Url->build(['action' => 'edit', (int)$r['id']]) ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
