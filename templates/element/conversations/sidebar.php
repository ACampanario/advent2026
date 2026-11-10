<?php
/**
 * Conversation list of the logged-in user.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Conversation> $conversations
 * @var \App\Model\Entity\Conversation|null $current
 */
?>
<?= $this->Html->link(
    __('New chat'),
    ['controller' => 'Conversations', 'action' => 'index'],
    ['class' => 'btn btn-primary w-100 mb-3'],
) ?>
<div class="list-group">
    <?php foreach ($conversations as $item) : ?>
        <?php $active = $current !== null && $current->id === $item->id; ?>
        <a href="<?= $this->Url->build(['controller' => 'Conversations', 'action' => 'view', $item->id]) ?>"
           class="list-group-item list-group-item-action<?= $active ? ' active' : '' ?>">
            <div class="text-truncate fw-semibold"><?= h($item->title) ?></div>
            <small class="<?= $active ? '' : 'text-muted' ?>">
                <?= h($item->created->i18nFormat('yyyy-MM-dd HH:mm')) ?>
                · <?= $this->Number->format((int)$item->total_tokens) ?> <?= __('tokens') ?>
                · $<?= $this->Number->format((float)$item->total_cost, ['places' => 5, 'precision' => 5]) ?>
            </small>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($conversations->isEmpty()) : ?>
    <p class="text-muted small mt-2"><?= __('No conversations yet.') ?></p>
<?php endif; ?>
