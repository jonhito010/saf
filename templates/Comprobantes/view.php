<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comprobante $comprobante
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Comprobante'), ['action' => 'edit', $comprobante->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Comprobante'), ['action' => 'delete', $comprobante->id], ['confirm' => __('Are you sure you want to delete # {0}?', $comprobante->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Comprobantes'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Comprobante'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="comprobantes view content">
            <h3><?= h($comprobante->id) ?></h3>
            <p class="actions" style="margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
                <?= $this->Html->link(__('Vista XML'), ['action' => 'vistaXml', $comprobante->id], ['class' => 'button', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
                <?= $this->Html->link(__('Descargar XML'), ['action' => 'xml', $comprobante->id], ['class' => 'button']) ?>
                <?= $this->Html->link(__('Vista PDF'), ['action' => 'vistaPdf', $comprobante->id], ['class' => 'button', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
                <?= $this->Html->link(__('Descargar PDF'), ['action' => 'descargarPDF', $comprobante->id], ['class' => 'button']) ?>
            </p>
            <table>
                <tr>
                    <th><?= __('RfcEmisor') ?></th>
                    <td><?= h($comprobante->rfcEmisor) ?></td>
                </tr>
                <tr>
                    <th><?= __('RfcReceptor') ?></th>
                    <td><?= h($comprobante->rfcReceptor) ?></td>
                </tr>
                <tr>
                    <th><?= __('Uuid') ?></th>
                    <td><?= h($comprobante->uuid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Serie') ?></th>
                    <td><?= h($comprobante->serie) ?></td>
                </tr>
                <tr>
                    <th><?= __('Folio') ?></th>
                    <td><?= h($comprobante->folio) ?></td>
                </tr>
                <tr>
                    <th><?= __('FormaPago') ?></th>
                    <td><?= h($comprobante->formaPago) ?></td>
                </tr>
                <tr>
                    <th>Carta Porte</th>
                    <td><?= (int)($comprobante->llevaCartaPorte ?? 0) === 1 ? 'Sí' : 'No' ?></td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $comprobante->has('user') ? $this->Html->link($comprobante->user->username, ['controller' => 'Users', 'action' => 'view', $comprobante->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($comprobante->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Subtotal') ?></th>
                    <td><?= $comprobante->subtotal === null ? '' : $this->Number->format($comprobante->subtotal) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total') ?></th>
                    <td><?= $comprobante->total === null ? '' : $this->Number->format($comprobante->total) ?></td>
                </tr>
                <tr>
                    <th><?= __('Descuento') ?></th>
                    <td><?= $comprobante->descuento === null ? '' : $this->Number->format($comprobante->descuento) ?></td>
                </tr>
                <tr>
                    <th><?= __('Estatus') ?></th>
                    <td><?= $comprobante->estatus === null ? '' : $this->Number->format($comprobante->estatus) ?></td>
                </tr>
                <tr>
                    <th><?= __('Enviado') ?></th>
                    <td><?= $comprobante->enviado === null ? '' : $this->Number->format($comprobante->enviado) ?></td>
                </tr>
                <tr>
                    <th><?= __('FechaEmision') ?></th>
                    <td><?= h($comprobante->fechaEmision) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('NombreEmisor') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comprobante->nombreEmisor)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('NombreReceptor') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comprobante->nombreReceptor)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Xml') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comprobante->xml)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Pdf') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comprobante->pdf)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
