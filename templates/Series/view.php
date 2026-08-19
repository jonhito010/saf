<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Series $series
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Series'), ['action' => 'edit', $series->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Series'), ['action' => 'delete', $series->id], ['confirm' => __('Are you sure you want to delete # {0}?', $series->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Series'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Series'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="series view content">
            <h3><?= h($series->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Serie') ?></th>
                    <td><?= h($series->serie) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($series->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('FolioIncial') ?></th>
                    <td><?= $series->folioIncial === null ? '' : $this->Number->format($series->folioIncial) ?></td>
                </tr>
                <tr>
                    <th><?= __('FolioActual') ?></th>
                    <td><?= $series->folioActual === null ? '' : $this->Number->format($series->folioActual) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
