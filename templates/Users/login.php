<?php
/**
 * Demo profile selector.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 */
?>
<div class="py-4">
    <h1 class="h3 mb-2"><?= __('Shop Assistant') ?></h1>
    <p class="text-muted mb-4"><?= __('Demo: choose a profile to enter. No password required.') ?></p>

    <div class="row g-3">
        <?php foreach ($users as $user) : ?>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5 card-title"><?= h($user->name) ?></h2>
                        <p class="card-text">
                            <span class="badge text-bg-secondary"><?= h($user->role) ?></span>
                            <span class="text-muted ms-1"><?= h($user->username) ?></span>
                        </p>
                        <?= $this->Form->create(null, ['url' => ['controller' => 'Users', 'action' => 'login']]) ?>
                        <?= $this->Form->hidden('user_id', ['value' => $user->id]) ?>
                        <?= $this->Form->button(
                            __('Enter as {0}', $user->username),
                            ['class' => 'btn btn-primary w-100'],
                        ) ?>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
