<?php
/**
 * Modal de documentos vencidos (primera pantalla tras iniciar sesión).
 *
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $documentosVencidosModal
 */
if (empty($documentosVencidosModal)) {
    return;
}
$total = count($documentosVencidosModal);
?>
<div id="saf-modal-vencidos" class="saf-modal is-open" role="dialog" aria-modal="true" aria-labelledby="saf-modal-vencidos-title" aria-hidden="false">
  <div class="saf-modal-backdrop" data-saf-modal-close tabindex="-1"></div>
  <div class="saf-modal-dialog">
    <div class="saf-modal-header">
      <div class="saf-modal-header-icon" aria-hidden="true">⚠</div>
      <div>
        <h2 id="saf-modal-vencidos-title" class="saf-modal-title">Documentos vencidos</h2>
        <p class="saf-modal-subtitle">
          Hay <?= $total === 1 ? '1 documento' : h((string)$total) . ' documentos' ?> vencido<?= $total === 1 ? '' : 's' ?> o por vencer que requieren atención.
        </p>
      </div>
    </div>
    <div class="saf-modal-body">
      <table class="saf-modal-table">
        <thead>
          <tr>
            <th>Módulo</th>
            <th>Documento</th>
            <th>Referencia</th>
            <th>Estatus</th>
            <th>Vencimiento</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($documentosVencidosModal as $item):
              $esVencido = ($item['estatus'] ?? '') === 'vencido';
          ?>
          <tr>
            <td><span class="saf-modal-tag"><?= h($item['modulo_label']) ?></span></td>
            <td><?= h($item['tipo_label']) ?></td>
            <td>
              <a href="<?= $this->Url->build($item['url']) ?>" class="saf-modal-link"><?= h($item['referencia']) ?></a>
            </td>
            <td>
              <span class="badge <?= $esVencido ? 'badge-red' : 'badge-amber' ?>"><?= h($item['estatus_label'] ?? '') ?></span>
            </td>
            <td class="saf-modal-fecha<?= $esVencido ? '' : ' saf-modal-fecha--warn' ?>"><?= h($item['fecha_vencimiento'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="saf-modal-footer">
      <a href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>" class="btn btn-ghost">Ver cumplimiento</a>
      <button type="button" class="btn btn-primary" data-saf-modal-close>Entendido</button>
    </div>
  </div>
</div>
