<?php
/**
 * Chat page: sidebar + transcript + composer. Used by index (new chat) and view.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Conversation> $conversations
 * @var \App\Model\Entity\Conversation|null $conversation
 * @var array{exchanges: list<array<string, mixed>>, totals: array<string, int|float>} $transcript
 */
$this->assign('title', $conversation ? $conversation->title : __('New chat'));
?>
<div class="row g-4">
    <aside class="col-lg-3">
        <?= $this->element('conversations/sidebar', ['conversations' => $conversations, 'current' => $conversation]) ?>
    </aside>

    <section class="col-lg-9 d-flex flex-column chat-panel">
        <header class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 border-bottom pb-2 mb-3">
            <h1 class="h5 mb-0"><?= h($conversation ? $conversation->title : __('New chat')) ?></h1>
            <?php if ($transcript['totals'] !== [] && $transcript['totals']['calls'] > 0) : ?>
                <?= $this->element('conversations/usage', [
                    'usage' => $transcript['totals'],
                    'prefix' => __('{0} model calls', $transcript['totals']['calls']) . ' · ',
                ]) ?>
            <?php endif; ?>
        </header>

        <div class="chat-messages flex-grow-1 mb-3" id="chat-messages">
            <?php if ($transcript['exchanges'] === []) : ?>
                <p class="text-muted"><?= __('Ask about your customers, orders, products or sales.') ?></p>
            <?php endif; ?>
            <?php foreach ($transcript['exchanges'] as $index => $exchange) : ?>
                <?= $this->element('conversations/exchange', ['exchange' => $exchange, 'index' => $index]) ?>
            <?php endforeach; ?>
        </div>

        <?= $this->element('conversations/composer', ['conversation' => $conversation]) ?>
    </section>
</div>
