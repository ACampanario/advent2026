<?php
/**
 * Main layout: Bootstrap 5 from the jsDelivr CDN (pinned version + SRI), own CSS/JS on top.
 *
 * @var \App\View\AppView $this
 */

/** @var \Authorization\IdentityInterface|null $identity */
$identity = $this->request->getAttribute('identity');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Shop Assistant') ?><?= $this->fetch('title') ? ' · ' . h($this->fetch('title')) : '' ?></title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', [
        'integrity' => 'sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
        'crossorigin' => 'anonymous',
    ]) ?>
    <?= $this->Html->css('chat') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand bg-dark" data-bs-theme="dark">
        <div class="container-fluid">
            <?= $this->Html->link(__('Shop Assistant'), '/', ['class' => 'navbar-brand']) ?>
            <?php if ($identity !== null) : ?>
                <div class="navbar-nav ms-auto align-items-center gap-2">
                    <span class="navbar-text text-light"><?= h($identity['name']) ?></span>
                    <span class="badge text-bg-info"><?= h($identity['role']) ?></span>
                    <?= $this->Html->link(
                        __('Log out'),
                        ['controller' => 'Users', 'action' => 'logout', 'plugin' => null],
                        ['class' => 'nav-link'],
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container py-4">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', [
        'integrity' => 'sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI',
        'crossorigin' => 'anonymous',
    ]) ?>
    <?= $this->Html->script('chat') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
